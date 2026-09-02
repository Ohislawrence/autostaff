<?php

namespace App\Http\Controllers;

use App\Models\AiRun;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $organization = current_org();
        $orgId = $organization->id;

        $stats = [
            'total_conversations' => $organization->conversations()->count(),
            'open_conversations' => $organization->conversations()->whereIn('status', ['open', 'ai_handling', 'waiting_customer'])->count(),
            'resolved_conversations' => $organization->conversations()->whereIn('status', ['resolved', 'closed'])->count(),
            'escalated_conversations' => $organization->conversations()->where('status', 'human_required')->count(),
            'total_messages' => $organization->messages()->count(),
            'ai_responses' => $organization->messages()->where('type', 'ai_response')->count(),
            'incoming_messages' => $organization->messages()->where('type', 'incoming')->count(),
            'total_leads' => $organization->leads()->count(),
            'converted_leads' => $organization->leads()->where('stage', 'won')->count(),
            'conversion_rate' => $organization->leads()->count() > 0 ? round(($organization->leads()->where('stage', 'won')->count() / $organization->leads()->count()) * 100, 1) : 0,
            'total_orders' => $organization->orders()->count(),
            'total_revenue' => (string) $organization->orders()->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total'),
            'ai_orders' => $organization->orders()->whereNotNull('ai_employee_id')->count(),
            'total_ai_runs' => AiRun::where('organization_id', $orgId)->count(),
            'total_ai_cost' => (string) round(AiRun::where('organization_id', $orgId)->sum('estimated_cost'), 2),
            'avg_response_time_ms' => (int) (AiRun::where('organization_id', $orgId)->avg('latency_ms') ?? 0),
            'total_tool_executions' => $organization->toolExecutions()->count(),
            'successful_tool_executions' => $organization->toolExecutions()->where('status', 'success')->count(),
            'monthly_conversations' => $this->getMonthlyData($orgId, 'conversations'),
            'monthly_messages' => $this->getMonthlyData($orgId, 'messages'),
            'monthly_ai_cost' => $this->getMonthlyAiCost($orgId),
        ];
        return Inertia::render('Analytics/Index', ['stats' => $stats]);
    }

    protected function getMonthlyData(int $orgId, string $table): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = DB::table($table)->where('organization_id', $orgId)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
            $months[] = ['month' => $date->format('M'), 'count' => $count];
        }
        return $months;
    }

    protected function getMonthlyAiCost(int $orgId): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $cost = AiRun::where('organization_id', $orgId)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('estimated_cost');
            $months[] = ['month' => $date->format('M'), 'cost' => round($cost, 2)];
        }
        return $months;
    }
}