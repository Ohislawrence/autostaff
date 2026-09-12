<?php

namespace App\Services\Onboarding;

use App\Models\AiEmployee;
use App\Models\Organization;
use App\Models\ProspectingCampaign;
use App\Models\Tool;
use App\Services\Prospecting\ProspectHunterService;
use App\Services\Prospecting\ProspectQualifierService;
use Illuminate\Support\Str;

/**
 * Turns the "Get Your First Customer" onboarding answers into a live
 * AI Sales Employee + a hunting ProspectingCampaign, then runs the first
 * Hunt → Qualify pass synchronously so the user sees scored prospects
 * immediately.
 */
class FirstCustomerService
{
    protected const SDR_TOOLS = [
        'hunt_icp',
        'qualify_prospect',
        'draft_outreach',
        'send_outreach',
        'get_prospecting_status',
        'create_lead',
        'transfer_to_human',
    ];

    public function launch(Organization $organization, array $input): array
    {
        // 1. Create the AI Sales Employee (SDR).
        $employee = $this->createSalesEmployee($organization, $input);

        // 2. Build the ProspectingCampaign from the ICP answers.
        $campaign = ProspectingCampaign::create([
            'organization_id' => $organization->id,
            'ai_employee_id' => $employee->id,
            'name' => 'First Customers — ' . ($organization->industry ?: $organization->name),
            'description' => 'Auto-generated campaign from onboarding.',
            'offer' => $this->buildOffer($input),
            'tone' => 'professional',
            'daily_limit' => 8, // keep the first hunt fast for a good onboarding experience
            'auto_outreach' => false,
            'icp' => $this->buildIcp($input),
            'status' => 'active',
        ]);

        // 3. Run Hunt → Qualify synchronously so prospects appear immediately.
        $hunter = app(ProspectHunterService::class);
        $qualifier = app(ProspectQualifierService::class);

        $hunt = $hunter->hunt($campaign);

        $campaign->prospects()->where('status', 'new')->get()
            ->each(function ($prospect) use ($qualifier) {
                try {
                    $qualifier->qualify($prospect);
                } catch (\Throwable $e) {
                    // Keep going — scoring is best-effort.
                }
            });

        return [
            'employee' => $employee,
            'campaign' => $campaign,
            'prospects' => $campaign->prospects()->orderByDesc('score')->get(),
            'created' => $hunt['created'] ?? 0,
        ];
    }

    protected function createSalesEmployee(Organization $organization, array $input): AiEmployee
    {
        $name = $input['employee_name'] ?? 'Alex — Sales Assistant';

        $employee = $organization->aiEmployees()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'role' => 'Sales Development Rep',
            'department' => 'revenue',
            'description' => 'Outbound AI sales assistant that finds, qualifies, and follows up with prospects.',
            'avatar' => '🎯',
            'personality' => 'Friendly, concise, and persistent without being pushy.',
            'tone' => 'professional',
            'language' => 'en',
            'system_instructions' => $this->sdrInstructions($organization, $input),
            'enabled_tools' => self::SDR_TOOLS,
            'is_active' => true,
        ]);

        $this->syncTools($employee);

        return $employee;
    }

    protected function syncTools(AiEmployee $employee): void
    {
        $toolIds = Tool::where('is_active', true)
            ->whereIn('identifier', self::SDR_TOOLS)
            ->pluck('id');

        $employee->tools()->sync(
            $toolIds->mapWithKeys(fn ($id) => [$id => [
                'is_allowed' => true,
                'requires_confirmation' => false,
            ]])->all()
        );
    }

    protected function sdrInstructions(Organization $organization, array $input): string
    {
        $offering = $input['offering'] ?? 'our product/service';

        return "You are {$organization->name}'s AI Sales Development Representative. "
            . "Your job is to find prospects who match our ideal customer profile, qualify them, "
            . "and start polite, personalized outreach.\n\n"
            . "What we sell: {$offering}. "
            . "Use the prospecting tools to hunt for prospects, score them 1-10, and draft short "
            . "outreach emails. Never invent facts about a prospect; work from the data the tools return. "
            . "When a prospect is clearly a strong fit or replies, flag them for a human to follow up.";
    }

    protected function buildOffer(array $input): string
    {
        $offering = trim((string) ($input['offering'] ?? ''));
        $price = trim((string) ($input['price_point'] ?? ''));

        if ($offering === '') {
            return '';
        }

        return $price !== '' ? "{$offering} (from {$price})" : $offering;
    }

    protected function buildIcp(array $input): array
    {
        $icp = $input['icp'] ?? [];

        return [
            'industry' => (array) ($icp['industry'] ?? []),
            'company_size' => $icp['company_size'] ?? null,
            'geography' => (array) ($icp['geography'] ?? []),
            'job_titles' => (array) ($icp['job_titles'] ?? []),
            'keywords' => (array) ($icp['keywords'] ?? []),
            'exclusions' => (array) ($icp['exclusions'] ?? []),
            'budget' => $icp['budget'] ?? null,
            'pain_points' => $icp['pain_points'] ?? null,
        ];
    }
}
