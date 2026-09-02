<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AiRun;
use App\Services\Platform\PlatformStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperationsController extends Controller
{
    public function __construct(protected PlatformStatsService $stats) {}

    // Phase K: Usage & Cost Management
    public function usage()
    {
        $currentMonth = now()->startOfMonth();
        $aiCostTotal = AiRun::where('created_at', '>=', $currentMonth)->sum('estimated_cost');
        $aiCostByOrg = AiRun::select('organization_id', DB::raw('SUM(estimated_cost) as total_cost'))
            ->with('organization:id,name')
            ->where('created_at', '>=', $currentMonth)
            ->groupBy('organization_id')->orderByDesc('total_cost')->take(20)->get()
            ->map(fn ($r) => ['name' => $r->organization?->name ?? 'Unknown', 'cost' => round($r->total_cost, 2)]);

        // Monthly cost trends
        $monthlyCost = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $monthlyCost[] = ['month' => $d->format('M'), 'cost' => round(AiRun::whereYear('created_at', $d->year)->whereMonth('created_at', $d->month)->sum('estimated_cost'), 2)];
        }

        $mrr = $this->stats->getMRR();

        return Inertia::render('Platform/Usage', [
            'mrr' => $mrr,
            'aiCostTotal' => round($aiCostTotal, 2),
            'grossContribution' => round($mrr - $aiCostTotal, 2),
            'aiCostByOrg' => $aiCostByOrg,
            'monthlyCost' => $monthlyCost,
        ]);
    }

    // Phase L: System Monitoring
    public function health()
    {
        return Inertia::render('Platform/Health', [
            'health' => $this->stats->getSystemHealth(),
            'queueStats' => $this->getQueueStats(),
        ]);
    }

    // Phase M: Queue Management
    public function queues()
    {
        $queues = ['default', 'ai', 'knowledge', 'webhooks', 'notifications', 'automation'];
        $data = [];

        foreach ($queues as $queue) {
            $pending = DB::table('jobs')->where('queue', $queue)->count();
            $failed = DB::table('failed_jobs')->where('queue', $queue)->count();
            $completed = DB::table('jobs')->where('queue', $queue)->where('reserved_at', '>', 0)->count();

            $data[] = [
                'name' => $queue,
                'pending' => $pending,
                'failed' => $failed,
                'processing' => $completed,
                'status' => $failed > 10 ? 'degraded' : ($pending > 100 ? 'busy' : 'healthy'),
            ];
        }

        return Inertia::render('Platform/Queues', ['queues' => $data]);
    }

    // Phase N: Failed Jobs
    public function failedJobs(Request $request)
    {
        $jobs = DB::table('failed_jobs')
            ->when($request->queue, fn ($q) => $q->where('queue', $request->queue))
            ->latest('failed_at')
            ->paginate(20);

        $queues = DB::table('failed_jobs')->distinct()->pluck('queue')->toArray();

        return Inertia::render('Platform/FailedJobs', [
            'jobs' => $jobs,
            'queues' => $queues,
            'filters' => $request->only(['queue']),
            'total' => DB::table('failed_jobs')->count(),
        ]);
    }

    public function retryFailedJob($id)
    {
        $job = DB::table('failed_jobs')->where('id', $id)->first();
        if ($job) {
            \Artisan::call('queue:retry', ['id' => [$id]]);
            return back()->with('success', 'Job retried.');
        }
        return back()->with('error', 'Job not found.');
    }

    public function clearFailedJobs()
    {
        \Artisan::call('queue:flush');
        return back()->with('success', 'All failed jobs cleared.');
    }

    // Phase O: AI Debugging
    public function aiRuns(Request $request)
    {
        $runs = AiRun::with(['aiEmployee:id,name', 'conversation:id,subject'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->organization_id, fn ($q) => $q->where('organization_id', $request->organization_id))
            ->latest()
            ->paginate(20);

        return Inertia::render('Platform/AiRuns', [
            'runs' => $runs,
            'filters' => $request->only(['status', 'organization_id']),
        ]);
    }

    public function aiRunDetail(AiRun $aiRun)
    {
        $aiRun->load(['aiEmployee:id,name', 'conversation:id,subject,organization_id', 'conversation.organization:id,name']);

        return Inertia::render('Platform/AiRunDetail', [
            'run' => $aiRun,
        ]);
    }

    protected function getQueueStats(): array
    {
        return [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'queues' => DB::table('jobs')->select('queue', DB::raw('count(*) as count'))->groupBy('queue')->get()->toArray(),
        ];
    }
}