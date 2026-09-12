<?php

namespace Database\Seeders;

use App\Models\BuyerPersona;
use Illuminate\Database\Seeder;

class BuyerPersonaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'E-commerce Founder',
                'avatar' => '🛒',
                'role_titles' => ['Founder', 'Owner', 'E-commerce Manager'],
                'demographics' => ['25–45', '2–10 staff', 'Shopify / WooCommerce store'],
                'goals' => ['Grow online sales', 'Stop missing orders after hours', 'Answer customer questions faster'],
                'pains' => ['Missed WhatsApp orders overnight', 'Abandoned carts', 'Unanswered DMs', 'Slow support'],
                'objections' => ['Too busy to set up', 'Tried a cheap bot before', 'Unsure it works with my store'],
                'buying_triggers' => ['Hiring first support person', 'Peak season / sales', 'Growing order volume'],
                'messaging_hooks' => ['Never lose another order', 'Hire a rep that never sleeps'],
                'value_props' => ['24/7 WhatsApp + web replies', 'Takes orders and answers questions', 'Plugs into Shopify/WooCommerce'],
                'channels' => ['Instagram', 'WhatsApp', 'Shopify community'],
                'current_solution' => 'Manual replies via phone and Instagram DMs',
                'keywords' => ['online store', 'e-commerce', 'shopify store', 'woocommerce', 'instagram shop'],
            ],
            [
                'name' => 'Clinic / Wellness Owner',
                'avatar' => '🩺',
                'role_titles' => ['Owner', 'Practice Manager', 'Clinic Director'],
                'demographics' => ['30–50', '5–30 staff', 'Clinic / dental / salon / gym'],
                'goals' => ['Reduce no-shows', 'Book more appointments', 'Free up the receptionist'],
                'pains' => ['No-shows and double bookings', 'Missed calls during consultations', 'After-hours enquiries'],
                'objections' => ['Patients prefer calling', 'Worried about privacy', 'Not technical'],
                'buying_triggers' => ['Hiring reception staff', 'Overwhelmed front desk', 'Expanding locations'],
                'messaging_hooks' => ['An AI receptionist that books appointments', 'Cut no-shows automatically'],
                'value_props' => ['Bookings + reminders', 'Reduces no-shows', 'Answers after hours'],
                'channels' => ['Facebook', 'Google Business', 'Local directories'],
                'current_solution' => 'Receptionist + phone + paper diary',
                'keywords' => ['clinic', 'dental practice', 'salon booking', 'spa appointments', 'gym'],
            ],
            [
                'name' => 'Agency Owner',
                'avatar' => '🤝',
                'role_titles' => ['Agency Owner', 'Founder', 'Managing Director'],
                'demographics' => ['30–45', '10–50 staff', 'Marketing / web-dev / IT agency'],
                'goals' => ['Add recurring revenue', 'Resell to clients', 'Reduce client churn'],
                'pains' => ['Thin margins', 'Project-based churn', 'Clients asking for AI they cannot deliver'],
                'objections' => ['Hard to white-label', 'Unclear margins', 'Onboarding effort'],
                'buying_triggers' => ['Client asking for AI', 'Looking for a new revenue line'],
                'messaging_hooks' => ['Launch your own AI-employee agency', 'Recurring revenue on every client'],
                'value_props' => ['White-label', 'Recurring rev-share', 'Done-for-you onboarding'],
                'channels' => ['LinkedIn', 'Agency communities', 'Freelance marketplaces'],
                'current_solution' => 'Project work + subcontractors',
                'keywords' => ['digital agency', 'marketing agency', 'web development agency', 'white label'],
            ],
            [
                'name' => 'Ops / CX Director',
                'avatar' => '📊',
                'role_titles' => ['Head of Operations', 'CX Director', 'Support Manager'],
                'demographics' => ['35–50', '10–100 staff', 'Retail / service / fintech'],
                'goals' => ['Deflect support volume', 'Consistent answers', 'Visibility into conversations'],
                'pains' => ['High support volume', 'Inconsistent responses', 'Compliance concerns'],
                'objections' => ['Security / governance', 'Integration with our stack', 'Change management'],
                'buying_triggers' => ['Support backlog', 'Audit / compliance push', 'Scaling headcount'],
                'messaging_hooks' => ['A governed AI workforce', 'Audit trails + analytics'],
                'value_props' => ['Enterprise security', 'Tenant isolation', 'RBAC + audit logs'],
                'channels' => ['LinkedIn', 'Industry events', 'Peer communities'],
                'current_solution' => 'In-house support team + helpdesk',
                'keywords' => ['customer experience', 'support automation', 'CX operations', 'helpdesk AI'],
            ],
            [
                'name' => 'International SaaS / Store Founder',
                'avatar' => '🌍',
                'role_titles' => ['Founder', 'CEO', 'Head of Growth'],
                'demographics' => ['25–45', 'Global customers', 'SaaS or Shopify store'],
                'goals' => ['Scale support', 'Convert more leads', 'Ship fast'],
                'pains' => ['Support backlog', 'No native AI agent for their stack', '24/7 coverage across timezones'],
                'objections' => ['Data residency', 'API reliability', 'Pricing'],
                'buying_triggers' => ['Rapid growth', 'Launching a new product', 'International expansion'],
                'messaging_hooks' => ['Live on web + email in minutes', 'API access to your stack'],
                'value_props' => ['Fast setup', 'Multi-channel', 'API-first'],
                'channels' => ['Twitter/X', 'Product Hunt', 'Developer communities'],
                'current_solution' => 'Intercom / Zendesk + manual processes',
                'keywords' => ['saas', 'shopify', 'customer support', 'ai chatbot', 'help desk'],
            ],
        ];

        foreach ($templates as $template) {
            BuyerPersona::updateOrCreate(
                ['name' => $template['name'], 'is_template' => true, 'organization_id' => null],
                array_merge($template, ['is_template' => true, 'organization_id' => null])
            );
        }
    }
}

