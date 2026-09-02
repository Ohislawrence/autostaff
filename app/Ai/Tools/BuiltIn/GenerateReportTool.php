<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\AiRun;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Order;
use App\Models\AutomationRun;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;

class GenerateReportTool extends BaseTool
{
    protected string $identifier = 'generate_report';
    protected string $name = 'Generate Report';
    protected string $description = 'Generate a business performance report (daily/weekly/monthly). Shows inquiries, conversions, orders, revenue, escalations, and AI activity metrics.';
    protected string $category = 'analytics';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'period' => ['type' => 'string', 'description' => 'daily, weekly, or monthly', 'enum' => ['daily', 'weekly', 'monthly']],
                'start_date' => ['type' => 'string', 'description' => 'Optional start date (YYYY-MM-DD). Overrides period if provided.'],
                'end_date' => ['type' => 'string', 'description' => 'Optional end date (YYYY-MM-DD). Overrides period if provided.'],
            ],
            'required' => ['period'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'period' => ['type' => 'string'],
                'report_text' => ['type' => 'string'],
                'metrics' => ['type' => 'object'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $period = $parameters['period'] ?? 'weekly';
        $symbol = Currency::symbol(tenant_currency());

        // Determine date range
        if (! empty($parameters['start_date'])) {
            $start = \Carbon\Carbon::parse($parameters['start_date'])->startOfDay();
            $end = ! empty($parameters['end_date'])
                ? \Carbon\Carbon::parse($parameters['end_date'])->endOfDay()
                : now()->endOfDay();
        } else {
            match ($period) {
                'daily' => $start = now()->startOfDay(),
                'monthly' => $start = now()->startOfMonth(),
                default => $start = now()->startOfWeek(),
            };
            $end = now()->endOfDay();
        }

        $periodLabel = match ($period) {
            'daily' => 'Today',
            'monthly' => 'This Month',
            default => 'This Week',
        };

        // Gather metrics
        $inquiries = Message::where('organization_id', $orgId)
            ->where('type', 'incoming')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $conversations = Conversation::where('organization_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $leadsCreated = Lead::where('organization_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $leadsWon = Lead::where('organization_id', $orgId)
            ->where('stage', 'won')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $ordersCount = Order::where('organization_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $ordersRevenue = Order::where('organization_id', $orgId)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$start, $end])
            ->sum('total');

        $escalations = Conversation::where('organization_id', $orgId)
            ->where('status', 'human_required')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $aiRuns = AiRun::where('organization_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $aiCost = round(AiRun::where('organization_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('estimated_cost'), 2);

        $automationRuns = AutomationRun::where('organization_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $aiHandledPercent = $conversations > 0
            ? round(($conversations - $escalations) / $conversations * 100, 1)
            : 100;

        // Build formatted report
        $report = "📊 *{$periodLabel} Report*\n" .
            "━━━━━━━━━━━━━━━\n" .
            "💬 Inquiries: {$inquiries}\n" .
            "🔄 Conversations: {$conversations}\n" .
            "🎯 Leads: {$leadsCreated}" . ($leadsWon > 0 ? " ({$leadsWon} won)" : '') . "\n" .
            "📦 Orders: {$ordersCount} — {$symbol}" . number_format($ordersRevenue) . "\n" .
            "🆘 Escalations: {$escalations}\n" .
            "━━━━━━━━━━━━━━━\n" .
            "🤖 AI handled {$aiHandledPercent}% autonomously\n" .
            "⚡ AI runs: {$aiRuns} | Cost: \${$aiCost}\n" .
            "🔧 Automations: {$automationRuns} runs\n" .
            "━━━━━━━━━━━━━━━\n" .
            "_{$start->format('d M')} — {$end->format('d M, Y')}_";

        return $this->success("{$periodLabel} report generated.", [
            'period' => $period,
            'report_text' => $report,
            'metrics' => [
                'inquiries' => $inquiries,
                'conversations' => $conversations,
                'leads_created' => $leadsCreated,
                'leads_won' => $leadsWon,
                'orders_count' => $ordersCount,
                'orders_revenue' => (string) $ordersRevenue,
                'escalations' => $escalations,
                'ai_runs' => $aiRuns,
                'ai_cost' => $aiCost,
                'ai_handle_rate' => $aiHandledPercent,
                'automation_runs' => $automationRuns,
            ],
        ]);
    }
}