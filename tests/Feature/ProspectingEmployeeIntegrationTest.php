<?php

namespace Tests\Feature;

use App\Ai\Tools\BuiltIn\GetProspectingStatusTool;
use App\Ai\Tools\BuiltIn\HuntIcpTool;
use App\Ai\Tools\BuiltIn\RunOutboundPipelineTool;
use App\Jobs\Prospecting\RunHuntJob;
use App\Models\AiEmployee;
use App\Models\Organization;
use App\Models\ProspectingCampaign;
use App\Models\Prospect;
use App\Services\Prospecting\ProspectHunterService;
use App\Services\Prospecting\ProspectQualifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProspectingEmployeeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Prospecting Org',
            'slug' => 'prospecting-org',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization', $this->organization);
        app()->instance('current_organization_id', $this->organization->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function ai_employee_links_to_its_campaigns_and_prospects_via_has_many_through(): void
    {
        $sdr = AiEmployee::create(['name' => 'SDR', 'role' => 'Sales Development Rep', 'department' => 'revenue', 'is_active' => true]);
        $other = AiEmployee::create(['name' => 'Support', 'role' => 'Support', 'is_active' => true]);

        $campaign = ProspectingCampaign::create([
            'organization_id' => $this->organization->id,
            'ai_employee_id' => $sdr->id,
            'name' => 'SDR Campaign',
            'icp' => ['industry' => ['SMEs']],
            'status' => 'active',
        ]);

        Prospect::create(['organization_id' => $this->organization->id, 'campaign_id' => $campaign->id, 'email' => 'a@example.com', 'status' => 'qualified', 'score' => 8]);
        Prospect::create(['organization_id' => $this->organization->id, 'campaign_id' => $campaign->id, 'email' => 'b@example.com', 'status' => 'new', 'score' => 5]);

        $this->assertSame(1, $sdr->prospectingCampaigns()->count());
        $this->assertSame(2, $sdr->prospects()->count());
        $this->assertSame(0, $other->prospectingCampaigns()->count());
        $this->assertSame(0, $other->prospects()->count());
    }

    #[Test]
    public function ai_employee_index_includes_campaign_and_prospect_counts(): void
    {
        $sdr = AiEmployee::create(['name' => 'SDR', 'role' => 'Sales Development Rep', 'is_active' => true]);

        $campaign = ProspectingCampaign::create([
            'organization_id' => $this->organization->id,
            'ai_employee_id' => $sdr->id,
            'name' => 'C1',
            'status' => 'active',
        ]);

        Prospect::create(['organization_id' => $this->organization->id, 'campaign_id' => $campaign->id, 'email' => 'x@example.com', 'status' => 'new']);

        $row = AiEmployee::withCount(['prospectingCampaigns as campaigns_count', 'prospects as prospects_count'])->find($sdr->id);

        $this->assertSame(1, (int) $row->campaigns_count);
        $this->assertSame(1, (int) $row->prospects_count);
    }

    #[Test]
    public function campaigns_and_prospects_are_filterable_by_employee(): void
    {
        $sdr = AiEmployee::create(['name' => 'SDR', 'role' => 'Sales Development Rep', 'is_active' => true]);
        $other = AiEmployee::create(['name' => 'Support', 'role' => 'Support', 'is_active' => true]);

        $sdrCampaign = ProspectingCampaign::create(['organization_id' => $this->organization->id, 'ai_employee_id' => $sdr->id, 'name' => 'SDR Campaign', 'status' => 'active']);
        ProspectingCampaign::create(['organization_id' => $this->organization->id, 'ai_employee_id' => $other->id, 'name' => 'Other Campaign', 'status' => 'active']);

        Prospect::create(['organization_id' => $this->organization->id, 'campaign_id' => $sdrCampaign->id, 'email' => 'sdr@example.com', 'status' => 'qualified']);

        $campaigns = ProspectingCampaign::where('organization_id', $this->organization->id)
            ->when($sdr->id, fn ($q) => $q->where('ai_employee_id', $sdr->id))
            ->get();

        $this->assertCount(1, $campaigns);
        $this->assertSame('SDR Campaign', $campaigns->first()->name);

        $prospects = Prospect::where('organization_id', $this->organization->id)
            ->when($sdr->id, fn ($q) => $q->whereHas('campaign', fn ($q) => $q->where('ai_employee_id', $sdr->id)))
            ->get();

        $this->assertCount(1, $prospects);
        $this->assertSame('sdr@example.com', $prospects->first()->email);
    }

    #[Test]
    public function hunt_icp_tool_attributes_the_campaign_to_the_employee(): void
    {
        $sdr = AiEmployee::create(['name' => 'SDR', 'role' => 'Sales Development Rep', 'is_active' => true]);

        $hunter = Mockery::mock(ProspectHunterService::class);
        $hunter->shouldReceive('hunt')->once()->andReturn(['created' => 0, 'sources' => ['web_search' => 0, 'ai_generated' => 0]]);
        app()->instance(ProspectHunterService::class, $hunter);

        $qualifier = Mockery::mock(ProspectQualifierService::class);
        app()->instance(ProspectQualifierService::class, $qualifier);

        $tool = new HuntIcpTool();
        $tool->execute(['_employee' => $sdr, 'campaign_name' => 'Attributed Campaign', 'industry' => ['SMEs']]);

        $campaign = ProspectingCampaign::where('name', 'Attributed Campaign')->first();
        $this->assertNotNull($campaign);
        $this->assertSame($sdr->id, $campaign->ai_employee_id);
    }

    #[Test]
    public function run_outbound_pipeline_tool_dispatches_the_chained_pipeline(): void
    {
        Queue::fake();

        $sdr = AiEmployee::create(['name' => 'SDR', 'role' => 'Sales Development Rep', 'is_active' => true]);

        $tool = new RunOutboundPipelineTool();
        $result = $tool->execute(['_employee' => $sdr, 'campaign_name' => 'Pipeline Campaign', 'offer' => 'Web design']);

        $campaign = ProspectingCampaign::where('name', 'Pipeline Campaign')->first();
        $this->assertNotNull($campaign);
        $this->assertSame($sdr->id, $campaign->ai_employee_id);

        Queue::assertPushed(RunHuntJob::class, function (RunHuntJob $job) {
            return count($job->chained) === 2;
        });

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function get_prospecting_status_reports_counts(): void
    {
        $campaign = ProspectingCampaign::create(['organization_id' => $this->organization->id, 'name' => 'Status Campaign', 'status' => 'active']);

        Prospect::create(['organization_id' => $this->organization->id, 'campaign_id' => $campaign->id, 'email' => 'q@example.com', 'status' => 'qualified', 'contacted_at' => now()]);

        $tool = new GetProspectingStatusTool();
        $result = $tool->execute([]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['stats']['found']);
        $this->assertSame(1, $result['stats']['qualified']);
        $this->assertSame(1, $result['stats']['contacted']);
        $this->assertStringContainsString('1 prospect(s) found', $result['message']);
    }
}
