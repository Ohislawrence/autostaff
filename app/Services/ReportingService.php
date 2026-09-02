<?php

namespace App\Services;

use App\Models\AutomationRun;
use App\Models\AiRun;
use App\Models\Conversation;
use App\Models\GeneratedReport;
use App\Models\KnowledgeGap;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Order;
use App\Models\Organization;
use App\Support\Currency;
use Carbon\Carbon;

/**
 * Scheduled reporting — computes business metrics for a period and persists a
 * GeneratedReport, plus a summary with knowledge gaps for the owner.
 */
class ReportingService
{
    /**
     * Generate (and persist) a report for a period.
     */
    public function generate(int $organizationId, string $period, ?Carbon $end = null): GeneratedReport
    {
        [$start, $endDate] = $this->periodRange($period, $end);

        $orders = $this->orders($organizationId, $start, $endDate);

        $metrics = [
            'conversations' => Conversation::where('organization_id', $organizationId)->whereBetween('created_at', [$start, $endDate])->count(),
            'inquiries' => Message::where('organization_id', $organizationId)->where('type', 'incoming')->whereBetween('created_at', [$start, $endDate])->count(),
            'new_customers' => \App\Models\Customer::where('organization_id', $organizationId)->whereBetween('created_at', [$start, $endDate])->count(),
            'leads' => Lead::where('organization_id', $organizationId)->whereBetween('created_at', [$start, $endDate])->count(),
            'leads_won' => Lead::where('organization_id', $organizationId)->where('stage', 'won')->whereBetween('created_at', [$start, $endDate])->count(),
            'orders' => $orders->count(),
            'revenue' => (string) $orders->sum('total'),
            'escalations' => Conversation::where('organization_id', $organizationId)->where('status', 'human_required')->whereBetween('updated_at', [$start, $endDate])->count(),
            'ai_runs' => AiRun::where('organization_id', $organizationId)->whereBetween('created_at', [$start, $endDate])->count(),
            'ai_cost' => (string) round(AiRun::where('organization_id', $organizationId)->whereBetween('created_at', [$start, $endDate])->sum('estimated_cost'), 2),
            'automation_runs' => AutomationRun::where('organization_id', $organizationId)->whereBetween('created_at', [$start, $endDate])->count(),
            'open_knowledge_gaps' => KnowledgeGap::where('organization_id', $organizationId)->where('status', 'open')->count(),
        ];

        $conversations = (int) ($metrics['conversations'] ?? 0);
        $escalations = (int) ($metrics['escalations'] ?? 0);
        $metrics['ai_resolution_rate'] = $conversations > 0
            ? round(($conversations - $escalations) / $conversations * 100, 1)
            : 100.0;

        $organization = Organization::find($organizationId);
        $summaryText = $this->summarize($organization, $period, $metrics, $start, $endDate);

        return GeneratedReport::create([
            'organization_id' => $organizationId,
            'period' => $period,
            'period_start' => $start->toDateString(),
            'period_end' => $endDate->toDateString(),
            'metrics' => $metrics,
            'summary_text' => $summaryText,
        ]);
    }

    protected function orders(int $organizationId, Carbon $start, Carbon $end)
    {
        return Order::where('organization_id', $organizationId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->get(['total']);
    }

    protected function periodRange(string $period, ?Carbon $end = null): array
    {
        $endDate = $end ?? now();

        $start = match ($period) {
            'daily' => $endDate->copy()->startOfDay(),
            'monthly' => $endDate->copy()->startOfMonth(),
            default => $endDate->copy()->startOfWeek(),
        };

        return [$start, $endDate->copy()->endOfDay()];
    }

    protected function summarize(?Organization $organization, string $period, array $metrics, Carbon $start, Carbon $end): string
    {
        $symbol = Currency::symbol($organization?->currency);

        return sprintf(
            "%s report (%s – %s): %d conversations, %d inquiries, %d leads, %d orders, %s%s revenue, %d escalations, %.1f%% AI resolution rate.",
            ucfirst($period),
            $start->toDateString(),
            $end->toDateString(),
            $metrics['conversations'] ?? 0,
            $metrics['inquiries'] ?? 0,
            $metrics['leads'] ?? 0,
            $metrics['orders'] ?? 0,
            $symbol,
            number_format((float) ($metrics['revenue'] ?? 0), 2),
            $metrics['escalations'] ?? 0,
            $metrics['ai_resolution_rate'] ?? 0,
        );
    }
}