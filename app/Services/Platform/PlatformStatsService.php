<?php

namespace App\Services\Platform;

use App\Models\AiRun;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\ToolExecution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformStatsService
{
    /**
     * Get the main platform dashboard KPIs.
     */
    public function getDashboardStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            // Organizations
            'total_organizations' => Organization::count(),
            'active_organizations' => Organization::where('is_active', true)->count(),
            'new_organizations' => Organization::where('created_at', '>=', $currentMonth)->count(),
            'new_organizations_last_month' => Organization::whereBetween('created_at', [$lastMonth, $currentMonth])->count(),

            // Users
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),

            // Revenue
            'mrr' => $this->getMRR(),
            'arr' => $this->getMRR() * 12,
            'revenue_this_month' => $this->getMonthlyRevenue(now()),
            'revenue_last_month' => $this->getMonthlyRevenue(now()->subMonth()),

            // AI
            'ai_conversations' => Conversation::count(),
            'ai_conversations_this_month' => Conversation::where('created_at', '>=', $currentMonth)->count(),
            'ai_messages' => Message::count(),
            'ai_messages_this_month' => Message::where('created_at', '>=', $currentMonth)->count(),
            'ai_tokens' => AiRun::sum(DB::raw('input_tokens + output_tokens')),
            'ai_cost' => round(AiRun::sum('estimated_cost') + ToolExecution::sum('estimated_cost'), 2),
            'ai_cost_this_month' => round(
                AiRun::where('created_at', '>=', $currentMonth)->sum('estimated_cost')
                + ToolExecution::where('created_at', '>=', $currentMonth)->sum('estimated_cost'),
                2
            ),
            'tool_execution_cost' => round(ToolExecution::sum('estimated_cost'), 2),
            'tool_execution_cost_this_month' => round(ToolExecution::where('created_at', '>=', $currentMonth)->sum('estimated_cost'), 2),
            'ai_runs' => AiRun::count(),
            'failed_ai_runs' => AiRun::where('status', 'failed')->count(),

            // Jobs
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'pending_jobs' => DB::table('jobs')->count(),

            // Subscriptions
            'total_subscriptions' => Subscription::where('status', 'active')->count(),
            'churned_this_month' => Subscription::where('status', 'cancelled')->where('cancelled_at', '>=', $currentMonth)->count(),

            // Charts
            'monthly_revenue' => $this->getRevenueChart(),
            'monthly_ai_activity' => $this->getAiActivityChart(),
            'monthly_organizations' => $this->getOrganizationChart(),
            'ai_cost_by_org' => $this->getTopAiSpenders(),
        ];
    }

    public function getMRR(): float
    {
        return Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }

    public function getMonthlyRevenue($date): float
    {
        return Subscription::where('status', 'active')
            ->where('starts_at', '<=', $date->endOfMonth())
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }

    public function getRevenueChart(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M'),
                'revenue' => round($this->getMonthlyRevenue($date), 2),
            ];
        }
        return $months;
    }

    public function getAiActivityChart(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M'),
                'conversations' => Conversation::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'ai_runs' => AiRun::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        }
        return $months;
    }

    public function getOrganizationChart(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M'),
                'new' => Organization::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        }
        return $months;
    }

    public function getTopAiSpenders(int $limit = 10): array
    {
        return AiRun::select('organization_id', DB::raw('SUM(estimated_cost) as total_cost'))
            ->with('organization:id,name')
            ->where('created_at', '>=', now()->startOfMonth())
            ->groupBy('organization_id')
            ->orderByDesc('total_cost')
            ->take($limit)
            ->get()
            ->map(fn ($row) => [
                'organization' => $row->organization?->name ?? 'Unknown',
                'cost' => round($row->total_cost, 2),
            ])
            ->toArray();
    }

    public function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'ai_provider' => $this->checkAiProvider(),
            'storage' => $this->checkStorage(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy', 'latency_ms' => null];
        } catch (\Exception $e) {
            return ['status' => 'down', 'error' => $e->getMessage()];
        }
    }

    protected function checkRedis(): array
    {
        if (! extension_loaded('redis') && ! class_exists(\Predis\Client::class)) {
            return ['status' => 'unavailable', 'reason' => 'Redis not configured'];
        }
        try {
            $start = microtime(true);
            $redis = app('redis');
            if (method_exists($redis, 'connection')) {
                $redis->connection()->ping();
            }
            $latency = round((microtime(true) - $start) * 1000, 2);
            return ['status' => 'healthy', 'latency_ms' => $latency];
        } catch (\Exception $e) {
            return ['status' => 'unavailable', 'reason' => 'Redis not available'];
        }
    }

    protected function checkQueue(): array
    {
        $failed = DB::table('failed_jobs')->count();
        $pending = DB::table('jobs')->count();
        return [
            'status' => $failed > 50 ? 'degraded' : 'healthy',
            'failed_count' => $failed,
            'pending_count' => $pending,
        ];
    }

    protected function checkAiProvider(): array
    {
        try {
            $provider = app(\App\Ai\Providers\AiProviderInterface::class);
            return [
                'status' => $provider->isAvailable() ? 'healthy' : 'degraded',
                'provider' => $provider->getName(),
            ];
        } catch (\Exception $e) {
            return ['status' => 'unknown', 'error' => $e->getMessage()];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disk = \Storage::disk('local');
            $free = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());
            $percentUsed = $total > 0 ? round((1 - $free / $total) * 100, 1) : 0;

            return [
                'status' => $percentUsed > 90 ? 'degraded' : 'healthy',
                'used_percent' => $percentUsed,
                'free_gb' => round($free / 1024 / 1024 / 1024, 1),
            ];
        } catch (\Exception $e) {
            return ['status' => 'unknown'];
        }
    }
}