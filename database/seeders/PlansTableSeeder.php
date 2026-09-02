<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansTableSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'For solo entrepreneurs and small businesses testing AI.',
                'price' => 45000,
                'usd_price' => 29,
                'currency' => 'NGN',
                'billing_period' => 'monthly',
                'max_ai_employees' => 1,
                'max_messages_per_month' => 500,
                'max_tool_calls_per_month' => 100,
                'max_knowledge_sources' => 50,
                'features' => [
                    '1 AI Employee',
                    'Web Chat only',
                    '500 messages/month',
                    '1 Knowledge Base (50 sources)',
                    'Basic CRM (lead tracking)',
                    '5 automations',
                    '5 team members',
                    'Email support'
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'business'],
            [
                'name' => 'Business',
                'description' => 'For growing businesses with multi-channel needs. Most popular!',
                'price' => 150000,
                'usd_price' => 99,
                'currency' => 'NGN',
                'billing_period' => 'monthly',
                'max_ai_employees' => 3,
                'max_messages_per_month' => 3000,
                'max_tool_calls_per_month' => 1000,
                'max_knowledge_sources' => 150,
                'features' => [
                    '3 AI Employees',
                    'All Channels (Web, Email, WhatsApp)',
                    '3,000 messages/month',
                    '3 Knowledge Bases (150 sources)',
                    'Full CRM (scoring, pipeline, analytics)',
                    'Appointments system',
                    'Commerce (products, orders)',
                    '20 automations',
                    '15 team members',
                    'Priority email support'
                ],
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'description' => 'For established businesses and agencies with high-volume automation.',
                'price' => 375000,
                'usd_price' => 249,
                'currency' => 'NGN',
                'billing_period' => 'monthly',
                'max_ai_employees' => 10,
                'max_messages_per_month' => 10000,
                'max_tool_calls_per_month' => 5000,
                'max_knowledge_sources' => 500,
                'features' => [
                    '10 AI Employees',
                    'All Channels + Webhooks',
                    '10,000 messages/month',
                    '5 Knowledge Bases (500 sources)',
                    'Custom Tools (webhook-based)',
                    'API Access (REST v1)',
                    'Advanced automations (50 workflows)',
                    '50 team members',
                    'Dedicated support (email + Slack)',
                    '99.5% SLA'
                ],
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'For large organizations with custom requirements. Contact sales.',
                'price' => 0,
                'usd_price' => 0,
                'currency' => 'NGN',
                'billing_period' => 'monthly',
                'max_ai_employees' => 999,
                'max_messages_per_month' => 1000000,
                'max_tool_calls_per_month' => 500000,
                'max_knowledge_sources' => 1000,
                'features' => [
                    'Unlimited AI Employees',
                    'Unlimited messages & tool calls',
                    'Unlimited Knowledge Bases',
                    'White-label option (add-on)',
                    'Custom integrations',
                    'Dedicated infrastructure (add-on)',
                    '500+ team members',
                    '24/7 phone support',
                    '99.9% SLA with credits',
                    'Quarterly business reviews'
                ],
                'is_active' => true,
                'sort_order' => 4,
            ]
        );
    }
}
