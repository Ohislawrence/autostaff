<?php

namespace App\Services\Guardrails;

use App\Models\AiRun;
use App\Models\Organization;
use App\Models\ToolExecution;
use Illuminate\Support\Facades\Log;

class CostGuardService
{
    /**
     * Check if the organization has exceeded its monthly AI budget.
     * Returns true if the tool call is allowed, false if budget exceeded.
     */
    public function checkBudget(int $organizationId, float $estimatedCost = 0): bool
    {
        try {
            $org = Organization::find($organizationId);
            if (! $org || ! $org->monthly_ai_budget_cents) {
                return true; // No budget set — unlimited
            }

            $budgetDollars = $org->monthly_ai_budget_cents / 100;

            // Sum this month's AI spend (LLM runs + tool executions)
            $thisMonthSpend = $this->spendThisMonth($organizationId);

            $projectedTotal = $thisMonthSpend + $estimatedCost;

            if ($projectedTotal > $budgetDollars) {
                Log::warning('AI budget would be exceeded', [
                    'organization_id' => $organizationId,
                    'current_spend' => $thisMonthSpend,
                    'projected' => $projectedTotal,
                    'budget' => $budgetDollars,
                ]);

                // Check if already exceeded
                if ($thisMonthSpend >= $budgetDollars) {
                    return false; // Already over budget
                }

                // Within budget but projected cost would exceed
                return $thisMonthSpend < $budgetDollars;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Cost guard check failed', ['error' => $e->getMessage()]);
            return true; // Fail open — don't block on guard error
        }
    }

    /**
     * Check if the organization is in a degraded/cost-saving mode.
     */
    public function isDegradedMode(int $organizationId): bool
    {
        try {
            $org = Organization::find($organizationId);
            if (! $org || ! $org->monthly_ai_budget_cents) {
                return false;
            }

            $budgetDollars = $org->monthly_ai_budget_cents / 100;
            $thisMonthSpend = $this->spendThisMonth($organizationId);

            // Degraded mode at 90% of budget
            $threshold = $budgetDollars * 0.9;
            return $thisMonthSpend >= $threshold;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the current month's AI spend for an organization.
     */
    public function getCurrentMonthSpend(int $organizationId): float
    {
        return round($this->spendThisMonth($organizationId), 2);
    }

    /**
     * Sum this month's AI spend across LLM runs and tool executions.
     */
    protected function spendThisMonth(int $organizationId): float
    {
        $aiRunSpend = AiRun::forOrganization($organizationId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('estimated_cost');

        $toolSpend = ToolExecution::forOrganization($organizationId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('estimated_cost');

        return (float) $aiRunSpend + (float) $toolSpend;
    }

    /**
     * Get the budget limit in dollars.
     */
    public function getBudgetLimit(int $organizationId): ?float
    {
        $org = Organization::find($organizationId);
        return $org?->monthly_ai_budget_cents
            ? round($org->monthly_ai_budget_cents / 100, 2)
            : null;
    }
}