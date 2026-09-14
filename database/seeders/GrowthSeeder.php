<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\MarketingChannel;
use App\Models\PlatformGoal;
use App\Models\PlatformTask;
use Illuminate\Database\Seeder;

class GrowthSeeder extends Seeder
{
    public function run(): void
    {
        // ----- Marketing channels (the three growth engines from the marketing plan) -----
        MarketingChannel::firstOrCreate(
            ['name' => 'Outbound (Prospecting Engine)'],
            ['type' => 'outbound', 'goal' => 'Run Hunt → Qualify → Outreach at scale against ICPs.', 'budget' => 0, 'status' => 'active']
        );
        MarketingChannel::firstOrCreate(
            ['name' => 'Agency Partners'],
            ['type' => 'partner', 'goal' => 'Recruit 15–25 white-label agency partners.', 'budget' => 0, 'status' => 'active']
        );
        MarketingChannel::firstOrCreate(
            ['name' => 'Content & SEO'],
            ['type' => 'content', 'goal' => 'Publish SEO pages + founder content weekly.', 'budget' => 1000, 'status' => 'active']
        );

        // ----- Daily tasks (founder-led cadence from the 30-day plan) -----
        PlatformTask::firstOrCreate(
            ['title' => 'Publish 2 SEO pages'],
            ['category' => 'marketing', 'priority' => 'high', 'recurrence' => 'weekly', 'description' => 'Ship content targeting priority keywords.']
        );
        PlatformTask::firstOrCreate(
            ['title' => 'Recruit 1 agency partner'],
            ['category' => 'marketing', 'priority' => 'high', 'recurrence' => 'weekly', 'description' => 'White-label/reseller outreach.']
        );
        PlatformTask::firstOrCreate(
            ['title' => 'Run outbound prospecting campaign'],
            ['category' => 'marketing', 'priority' => 'normal', 'recurrence' => 'daily', 'description' => 'Review the Prospecting engine and refresh ICP.']
        );
        PlatformTask::firstOrCreate(
            ['title' => 'Review activation funnel'],
            ['category' => 'ops', 'priority' => 'normal', 'recurrence' => 'weekly', 'description' => 'Check time-to-first-resolved-conversation and churn.']
        );

        // ----- Goals / progress (mirrors the marketing plan KPI table) -----
        PlatformGoal::updateOrCreate(
            ['title' => 'Reach $1M ARR in 6 months'],
            ['metric_key' => 'usd_arr', 'target' => 1000000, 'unit' => 'currency', 'period' => 'custom', 'start_at' => now(), 'end_at' => now()->addMonths(6), 'color' => 'text-teal-600']
        );
        PlatformGoal::updateOrCreate(
            ['title' => 'Hit $83k MRR'],
            ['metric_key' => 'usd_mrr', 'target' => 83000, 'unit' => 'currency', 'period' => 'custom', 'start_at' => now(), 'end_at' => now()->addMonths(6), 'color' => 'text-blue-600']
        );
        PlatformGoal::updateOrCreate(
            ['title' => 'Reach 900 paying customers'],
            ['metric_key' => 'customers', 'target' => 900, 'unit' => 'count', 'period' => 'custom', 'start_at' => now(), 'end_at' => now()->addMonths(6), 'color' => 'text-purple-600']
        );
        PlatformGoal::updateOrCreate(
            ['title' => 'Keep activation rate ≥ 40%'],
            ['metric_key' => 'activation_rate', 'target' => 40, 'unit' => 'percent', 'period' => 'month', 'color' => 'text-green-600']
        );
        PlatformGoal::updateOrCreate(
            ['title' => 'Keep gross churn < 3%'],
            ['metric_key' => 'churn_rate', 'target' => 3, 'unit' => 'percent', 'period' => 'month', 'color' => 'text-orange-600']
        );

        // ----- Achievements (gamified milestones, auto-evaluated) -----
        $achievements = [
            ['key' => 'first_customer', 'name' => 'First Blood', 'icon' => '🩸', 'points' => 10, 'condition_key' => 'customers', 'target' => 1, 'sort_order' => 1, 'description' => 'Get your first paying customer.'],
            ['key' => 'ten_customers', 'name' => '10 Clubs', 'icon' => '🏢', 'points' => 25, 'condition_key' => 'customers', 'target' => 10, 'sort_order' => 2, 'description' => 'Reach 10 paying customers.'],
            ['key' => 'hundred_customers', 'name' => 'Century', 'icon' => '💯', 'points' => 100, 'condition_key' => 'customers', 'target' => 100, 'sort_order' => 3, 'description' => 'Reach 100 paying customers.'],
            ['key' => 'mrr_10k', 'name' => '$10k MRR', 'icon' => '💵', 'points' => 150, 'condition_key' => 'usd_mrr', 'target' => 10000, 'sort_order' => 4, 'description' => 'Reach $10,000 MRR.'],
            ['key' => 'mrr_50k', 'name' => '$50k MRR', 'icon' => '💰', 'points' => 400, 'condition_key' => 'usd_mrr', 'target' => 50000, 'sort_order' => 5, 'description' => 'Reach $50,000 MRR.'],
            ['key' => 'mrr_83k', 'name' => '$83k MRR', 'icon' => '🚀', 'points' => 750, 'condition_key' => 'usd_mrr', 'target' => 83000, 'sort_order' => 6, 'description' => 'Hit the $1M ARR run-rate milestone.'],
            ['key' => 'prospects_100', 'name' => 'Prospector', 'icon' => '🎯', 'points' => 50, 'condition_key' => 'prospects_contacted', 'target' => 100, 'sort_order' => 7, 'description' => 'Contact 100 prospects.'],
            ['key' => 'prospects_1000', 'name' => 'Pipeline Builder', 'icon' => '🔧', 'points' => 300, 'condition_key' => 'prospects_contacted', 'target' => 1000, 'sort_order' => 8, 'description' => 'Contact 1,000 prospects.'],
            ['key' => 'replies_100', 'name' => 'Conversation Starter', 'icon' => '💬', 'points' => 200, 'condition_key' => 'replies', 'target' => 100, 'sort_order' => 9, 'description' => 'Get 100 prospect replies.'],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(['key' => $achievement['key']], $achievement);
        }
    }
}
