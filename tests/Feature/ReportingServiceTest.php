<?php

namespace Tests\Feature;

use App\Models\GeneratedReport;
use App\Models\Organization;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected ReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Report Org',
            'slug' => 'report-org',
            'currency' => 'NGN',
            'onboarding_completed' => true,
        ]);

        $this->service = new ReportingService();
    }

    #[Test] public function it_generates_a_report_with_metrics()
    {
        $report = $this->service->generate($this->organization->id, 'weekly');

        $this->assertNotNull($report->id);
        $this->assertSame('weekly', $report->period);
        $this->assertArrayHasKey('conversations', $report->metrics);
        $this->assertArrayHasKey('revenue', $report->metrics);
        $this->assertArrayHasKey('open_knowledge_gaps', $report->metrics);
        $this->assertNotNull($report->summary_text);
    }

    #[Test] public function it_computes_resolution_rate()
    {
        $report = $this->service->generate($this->organization->id, 'weekly');

        $this->assertArrayHasKey('ai_resolution_rate', $report->metrics);
        // No conversations yet → 100% resolution rate (JSON cast may normalize to int 100).
        $this->assertEquals(100, $report->metrics['ai_resolution_rate']);
    }

    #[Test] public function it_persists_to_generated_reports_table()
    {
        $this->service->generate($this->organization->id, 'monthly');

        $this->assertSame(1, GeneratedReport::count());
    }
}