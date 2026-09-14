<?php

namespace App\Services\Platform;

use App\Models\Achievement;
use App\Models\AiEmployee;
use App\Models\Conversation;
use App\Models\MarketingChannel;
use App\Models\Organization;
use App\Models\PlatformGoal;
use App\Models\Prospect;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserAchievement;

class GrowthService
{
    /**
     * The metric keys available for goals + achievements, with human labels.
     */
    public function metricKeys(): array
    {
        return [
            'mrr' => 'Monthly Recurring Revenue (NGN)',
            'arr' => 'Annual Recurring Revenue (NGN)',
            'usd_mrr' => 'Monthly Recurring Revenue (USD)',
            'usd_arr' => 'Annual Recurring Revenue (USD)',
            'customers' => 'Paying Customers',
            'organizations' => 'Organizations',
            'new_organizations' => 'New Organizations (this month)',
            'active_users' => 'Active Users',
            'conversations' => 'Conversations',
            'resolved_conversations' => 'Resolved Conversations',
            'ai_employees' => 'AI Employees',
            'prospects' => 'Prospects Discovered',
            'prospects_contacted' => 'Prospects Contacted',
            'prospects_qualified' => 'Prospects Qualified',
            'replies' => 'Prospect Replies',
            'activation_rate' => 'Activation Rate (%)',
            'free_to_paid' => 'Free → Paid Rate (%)',
            'churn' => 'Churn (this month)',
            'churn_rate' => 'Gross Churn Rate (%)',
        ];
    }

    /**
     * Compute the live current value for a metric key. Tenant-scoped models are
     * queried without tenancy so the platform owner always sees the whole fleet.
     */
    public function currentValue(string $metricKey): float
    {
        return match ($metricKey) {
            'mrr' => (float) $this->mrr(),
            'arr' => (float) ($this->mrr() * 12),
            'usd_mrr' => (float) $this->usdMrr(),
            'usd_arr' => (float) ($this->usdMrr() * 12),
            'customers' => (float) Subscription::withoutTenancy()
                ->where('status', 'active')->distinct()->count('organization_id'),
            'organizations' => (float) Organization::count(),
            'new_organizations' => (float) Organization::where('created_at', '>=', now()->startOfMonth())->count(),
            'active_users' => (float) User::where('is_active', true)->count(),
            'conversations' => (float) Conversation::withoutTenancy()->count(),
            'resolved_conversations' => (float) Conversation::withoutTenancy()
                ->whereIn('status', ['resolved', 'closed'])->count(),
            'ai_employees' => (float) AiEmployee::withoutTenancy()->count(),
            'prospects' => (float) Prospect::count(),
            'prospects_contacted' => (float) Prospect::whereNotNull('contacted_at')->count(),
            'prospects_qualified' => (float) Prospect::where('status', 'qualified')->count(),
            'replies' => (float) Prospect::whereNotNull('replied_at')->count(),
            'activation_rate' => $this->percentage(
                Conversation::withoutTenancy()->distinct()->count('organization_id'),
                Organization::count()
            ),
            'free_to_paid' => $this->percentage(
                Subscription::withoutTenancy()->where('status', 'active')->distinct()->count('organization_id'),
                Organization::count()
            ),
            'churn' => (float) Subscription::withoutTenancy()
                ->where('status', 'cancelled')->where('cancelled_at', '>=', now()->startOfMonth())->count(),
            'churn_rate' => $this->percentage(
                Subscription::withoutTenancy()->where('status', 'cancelled')
                    ->where('cancelled_at', '>=', now()->startOfMonth())->count(),
                Subscription::withoutTenancy()->where('status', 'active')->count()
            ),
            default => 0.0,
        };
    }

    public function mrr(): float
    {
        return (float) Subscription::withoutTenancy()
            ->where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }

    public function usdMrr(): float
    {
        return (float) Subscription::withoutTenancy()
            ->where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.usd_price');
    }

    protected function percentage(float|int $numerator, float|int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    /**
     * Compute the progress status for a goal based on time elapsed vs. progress made.
     */
    public function goalStatus(PlatformGoal $goal, float $current, float $target): string
    {
        if ($target <= 0) {
            return 'behind';
        }

        $progress = $current / $target;

        if ($goal->start_at && $goal->end_at) {
            $start = $goal->start_at->getTimestamp();
            $end = $goal->end_at->getTimestamp();
            $now = now()->getTimestamp();

            if ($now >= $end) {
                return $progress >= 1 ? 'on-track' : 'behind';
            }

            if ($end > $start) {
                $elapsed = max(($now - $start) / ($end - $start), 0);

                return $progress >= $elapsed ? 'on-track' : 'behind';
            }
        }

        return $progress >= 1 ? 'on-track' : 'behind';
    }

    /**
     * Goals enriched with live current value, progress %, and status.
     */
    public function getGoals(): array
    {
        return PlatformGoal::orderByRaw("status = 'active' desc")->orderBy('created_at')->get()
            ->map(function (PlatformGoal $goal) {
                $current = $this->currentValue($goal->metric_key);
                $target = (float) $goal->target;
                $percent = $target > 0 ? min(100, round(($current / $target) * 100, 1)) : 0;

                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'metric_key' => $goal->metric_key,
                    'metric_label' => $this->metricKeys()[$goal->metric_key] ?? $goal->metric_key,
                    'current' => $current,
                    'target' => $target,
                    'unit' => $goal->unit,
                    'percent' => $percent,
                    'period' => $goal->period,
                    'color' => $goal->color,
                    'status' => $goal->status,
                    'progress_status' => $this->goalStatus($goal, $current, $target),
                ];
            })->toArray();
    }

    /**
     * Evaluate (and persist) progress for every achievement against live metrics.
     */
    public function evaluateAchievements(?User $user = null): void
    {
        $users = $user ? collect([$user]) : User::where('is_active', true)->get();
        $achievements = Achievement::orderBy('sort_order')->get();

        foreach ($users as $u) {
            foreach ($achievements as $achievement) {
                $current = $this->currentValue($achievement->condition_key);
                $target = (float) $achievement->target;
                $unlocked = $target > 0 && $current >= $target;

                UserAchievement::updateOrCreate(
                    ['user_id' => $u->id, 'achievement_id' => $achievement->id],
                    [
                        'progress' => min($current, $target),
                        'target' => $target,
                        'unlocked_at' => $unlocked
                            ? (UserAchievement::where('user_id', $u->id)
                                ->where('achievement_id', $achievement->id)
                                ->value('unlocked_at') ?? now())
                            : null,
                    ]
                );
            }
        }
    }

    /**
     * Achievements enriched with live progress for a given user.
     */
    public function getAchievements(User $user): array
    {
        return Achievement::orderBy('sort_order')->get()
            ->map(function (Achievement $achievement) use ($user) {
                $unlock = UserAchievement::where('user_id', $user->id)
                    ->where('achievement_id', $achievement->id)
                    ->first();

                $progress = (float) ($unlock?->progress ?? 0);
                $target = (float) $achievement->target;
                $percent = $target > 0 ? min(100, round(($progress / $target) * 100, 1)) : 0;

                return [
                    'id' => $achievement->id,
                    'key' => $achievement->key,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'points' => $achievement->points,
                    'progress' => $progress,
                    'target' => $target,
                    'percent' => $percent,
                    'unlocked' => (bool) $unlock?->unlocked_at,
                    'unlocked_at' => $unlock?->unlocked_at,
                ];
            })->toArray();
    }

    /**
     * Rollup of marketing performance per channel.
     */
    public function getMarketingStats(): array
    {
        $channels = MarketingChannel::with('metrics')->orderBy('name')->get();

        $totals = ['spend' => 0.0, 'leads' => 0.0, 'signups' => 0.0, 'mrr' => 0.0];

        $rows = $channels->map(function (MarketingChannel $channel) use (&$totals) {
            $spend = (float) $channel->metrics->where('metric', 'spend')->sum('value');
            $leads = (float) $channel->metrics->where('metric', 'leads')->sum('value');
            $signups = (float) $channel->metrics->where('metric', 'signups')->sum('value');
            $mrr = (float) $channel->metrics->where('metric', 'mrr')->sum('value');

            $totals['spend'] += $spend;
            $totals['leads'] += $leads;
            $totals['signups'] += $signups;
            $totals['mrr'] += $mrr;

            return [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
                'goal' => $channel->goal,
                'budget' => (float) $channel->budget,
                'status' => $channel->status,
                'start_at' => $channel->start_at,
                'end_at' => $channel->end_at,
                'notes' => $channel->notes,
                'spend' => $spend,
                'leads' => $leads,
                'signups' => $signups,
                'mrr' => $mrr,
                'cac' => $signups > 0 ? round($spend / $signups, 2) : null,
            ];
        })->toArray();

        return ['channels' => $rows, 'totals' => $totals];
    }

    /**
     * Channel types + metric names, for building forms in the UI.
     */
    public function channelTypes(): array
    {
        return ['content', 'seo', 'ads', 'partner', 'outbound', 'referral', 'social', 'email', 'other'];
    }

    public function metricNames(): array
    {
        return ['impressions', 'clicks', 'spend', 'leads', 'signups', 'mrr'];
    }

    /**
     * Auto-log a snapshot of live marketing metrics for the given channel types.
     * Used by the scheduled command so spend/leads/signups/mrr stay current without manual entry.
     */
    public function autoLogMetrics(): int
    {
        $logged = 0;
        $today = now()->toDateString();

        // Outbound channel: auto-derived from the prospecting engine.
        MarketingChannel::where('type', 'outbound')->get()
            ->each(function (MarketingChannel $channel) use ($today, &$logged) {
                $this->logMetric($channel, 'leads', Prospect::whereNotNull('contacted_at')->count(), $today);
                $this->logMetric($channel, 'signups', Organization::count(), $today);
                $logged += 2;
            });

        // Growth channels: signups + MRR auto-derived from the live business.
        MarketingChannel::whereIn('type', ['referral', 'partner', 'content', 'seo', 'social'])->get()
            ->each(function (MarketingChannel $channel) use ($today, &$logged) {
                $this->logMetric($channel, 'signups', Organization::where('created_at', '>=', now()->startOfMonth())->count(), $today);
                $this->logMetric($channel, 'mrr', $this->mrr(), $today);
                $logged += 2;
            });

        return $logged;
    }

    protected function logMetric(MarketingChannel $channel, string $metric, float $value, string $recordedOn): void
    {
        $channel->metrics()->updateOrCreate(
            ['channel_id' => $channel->id, 'metric' => $metric, 'recorded_on' => $recordedOn],
            ['value' => $value]
        );
    }
}
