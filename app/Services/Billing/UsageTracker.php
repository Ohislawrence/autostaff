<?php

namespace App\Services\Billing;

use App\Models\Organization;
use App\Models\Message;
use App\Models\ToolExecution;
use App\Models\KnowledgeSource;
use App\Models\AiEmployee;
use Carbon\Carbon;

class UsageTracker
{
    /**
     * Get current usage for an organization.
     */
    public function getUsage(Organization $organization): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        
        return [
            'ai_employees' => $organization->aiEmployees()->count(),
            'messages_this_month' => Message::where('organization_id', $organization->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'tool_calls_this_month' => ToolExecution::where('organization_id', $organization->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'knowledge_sources' => KnowledgeSource::where('organization_id', $organization->id)->count(),
            'automations' => $organization->automations()->count(),
            'team_members' => $organization->users()->count(),
        ];
    }

    /**
     * Check if organization is at or over limit for a specific resource.
     */
    public function isAtLimit(Organization $organization, string $resource): bool
    {
        $plan = $this->resolvePlan($organization);

        if (!$plan) {
            return true; // No plan available = restricted
        }

        $usage = $this->getUsage($organization);

        return match ($resource) {
            'ai_employees' => $usage['ai_employees'] >= $plan->max_ai_employees,
            'messages' => $usage['messages_this_month'] >= $plan->max_messages_per_month,
            'tool_calls' => $usage['tool_calls_this_month'] >= $plan->max_tool_calls_per_month,
            'knowledge_sources' => $usage['knowledge_sources'] >= $plan->max_knowledge_sources,
            default => false,
        };
    }

    /**
     * Resolve the active plan for an organization, falling back to the Free
     * plan so unsubscribed orgs can still use the platform within free limits.
     */
    protected function resolvePlan(Organization $organization): ?\App\Models\Plan
    {
        $subscription = $organization->subscriptions()->where('status', 'active')->latest()->first();

        if ($subscription && $subscription->plan) {
            return $subscription->plan;
        }

        return \App\Models\Plan::where('slug', 'free')->where('is_active', true)->first();
    }

    /**
     * Get usage percentage for a specific resource.
     */
    public function getUsagePercentage(Organization $organization, string $resource): int
    {
        $plan = $this->resolvePlan($organization);
        
        if (!$plan) {
            return 100;
        }

        $usage = $this->getUsage($organization);

        $percentage = match ($resource) {
            'ai_employees' => ($usage['ai_employees'] / max($plan->max_ai_employees, 1)) * 100,
            'messages' => ($usage['messages_this_month'] / max($plan->max_messages_per_month, 1)) * 100,
            'tool_calls' => ($usage['tool_calls_this_month'] / max($plan->max_tool_calls_per_month, 1)) * 100,
            'knowledge_sources' => ($usage['knowledge_sources'] / max($plan->max_knowledge_sources, 1)) * 100,
            default => 0,
        };

        return min((int) $percentage, 100);
    }

    /**
     * Check if organization should see a warning (80%+ usage).
     */
    public function shouldShowWarning(Organization $organization, string $resource): bool
    {
        return $this->getUsagePercentage($organization, $resource) >= 80;
    }

    /**
     * Get next upgrade plan for an organization.
     */
    public function getUpgradePlan(Organization $organization): ?array
    {
        $currentPlan = $this->resolvePlan($organization);

        if (!$currentPlan) {
            return null;
        }

        $nextPlan = \App\Models\Plan::where('is_active', true)
            ->where('sort_order', '>', $currentPlan->sort_order)
            ->orderBy('sort_order')
            ->first();

        return $nextPlan ? ['plan' => $nextPlan, 'is_downgrade' => false] : null;
    }

    /**
     * Get all limits with current usage.
     */
    public function getLimitsWithUsage(Organization $organization): array
    {
        $usage = $this->getUsage($organization);
        $plan = $this->resolvePlan($organization);

        if (!$plan) {
            return [
                'plan' => null,
                'limits' => [],
                'usage' => $usage,
            ];
        }

        return [
            'plan' => $plan,
            'limits' => [
                'ai_employees' => [
                    'limit' => $plan->max_ai_employees,
                    'used' => $usage['ai_employees'],
                    'percentage' => $this->getUsagePercentage($organization, 'ai_employees'),
                    'at_limit' => $this->isAtLimit($organization, 'ai_employees'),
                ],
                'messages' => [
                    'limit' => $plan->max_messages_per_month,
                    'used' => $usage['messages_this_month'],
                    'percentage' => $this->getUsagePercentage($organization, 'messages'),
                    'at_limit' => $this->isAtLimit($organization, 'messages'),
                ],
                'tool_calls' => [
                    'limit' => $plan->max_tool_calls_per_month,
                    'used' => $usage['tool_calls_this_month'],
                    'percentage' => $this->getUsagePercentage($organization, 'tool_calls'),
                    'at_limit' => $this->isAtLimit($organization, 'tool_calls'),
                ],
                'knowledge_sources' => [
                    'limit' => $plan->max_knowledge_sources,
                    'used' => $usage['knowledge_sources'],
                    'percentage' => $this->getUsagePercentage($organization, 'knowledge_sources'),
                    'at_limit' => $this->isAtLimit($organization, 'knowledge_sources'),
                ],
            ],
            'usage' => $usage,
        ];
    }
}
