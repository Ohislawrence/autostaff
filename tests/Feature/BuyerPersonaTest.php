<?php

namespace Tests\Feature;

use App\Models\BuyerPersona;
use App\Models\Organization;
use App\Models\ProspectingCampaign;
use App\Services\Prospecting\CampaignCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BuyerPersonaTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Persona Org',
            'slug' => 'persona-org',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);
    }

    #[Test]
    public function persona_casts_list_fields_to_arrays(): void
    {
        $persona = BuyerPersona::create([
            'organization_id' => $this->organization->id,
            'name' => 'Dental Clinic Owner',
            'avatar' => '🦷',
            'role_titles' => ['Owner', 'Practice Manager'],
            'pains' => ['No-shows', 'Missed calls'],
        ]);

        $this->assertSame(['Owner', 'Practice Manager'], $persona->role_titles);
        $this->assertSame(['No-shows', 'Missed calls'], $persona->pains);
    }

    #[Test]
    public function persona_builds_a_prompt_summary(): void
    {
        $persona = BuyerPersona::create([
            'organization_id' => $this->organization->id,
            'name' => 'Dental Clinic Owner',
            'role_titles' => ['Owner', 'Practice Manager'],
            'pains' => ['No-shows', 'Missed calls'],
            'goals' => ['Fill the schedule'],
            'current_solution' => 'Receptionist + phone',
        ]);

        $summary = $persona->toPromptSummary();

        $this->assertStringContainsString('Persona: Dental Clinic Owner', $summary);
        $this->assertStringContainsString('Pain points: No-shows; Missed calls', $summary);
        $this->assertStringContainsString('Goals: Fill the schedule', $summary);
        $this->assertStringContainsString('Current solution / status quo: Receptionist + phone', $summary);
    }

    #[Test]
    public function campaign_creator_snapshots_the_selected_persona(): void
    {
        $persona = BuyerPersona::create([
            'organization_id' => $this->organization->id,
            'name' => 'E-commerce Founder',
            'role_titles' => ['Founder'],
            'pains' => ['Missed orders'],
        ]);

        $campaign = app(CampaignCreator::class)->create([
            'name' => 'Persona Campaign',
            'buyer_persona_id' => $persona->id,
        ], $this->organization);

        $this->assertSame($persona->id, $campaign->buyer_persona_id);
        $this->assertSame('E-commerce Founder', $campaign->buyer_persona_snapshot['name']);
        $this->assertStringContainsString('Pain points: Missed orders', $campaign->personaPromptSummary());
    }

    #[Test]
    public function template_can_be_replicated_into_an_organization(): void
    {
        $template = BuyerPersona::create([
            'organization_id' => null,
            'name' => 'Template Persona',
            'is_template' => true,
            'pains' => ['Pain A'],
        ]);

        $copy = $template->replicate();
        $copy->organization_id = $this->organization->id;
        $copy->is_template = false;
        $copy->save();

        $this->assertNotNull($copy->id);
        $this->assertSame($this->organization->id, $copy->organization_id);
        $this->assertFalse((bool) $copy->is_template);
        $this->assertSame(['Pain A'], $copy->pains);
        $this->assertNotSame($template->uuid, $copy->uuid);
    }

    #[Test]
    public function campaign_creator_rejects_a_persona_from_another_organization(): void
    {
        $other = Organization::create(['name' => 'Other Org', 'slug' => 'other-org']);
        $persona = BuyerPersona::create([
            'organization_id' => $other->id,
            'name' => 'Foreign Persona',
        ]);

        $campaign = app(CampaignCreator::class)->create([
            'name' => 'No Persona Campaign',
            'buyer_persona_id' => $persona->id,
        ], $this->organization);

        $this->assertNull($campaign->buyer_persona_id);
        $this->assertNull($campaign->buyer_persona_snapshot);
        $this->assertNull($campaign->personaPromptSummary());
    }
}
