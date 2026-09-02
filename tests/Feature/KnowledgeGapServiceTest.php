<?php

namespace Tests\Feature;

use App\Models\KnowledgeGap;
use App\Models\Organization;
use App\Services\Knowledge\KnowledgeGapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeGapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected KnowledgeGapService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Gap Org',
            'slug' => 'gap-org',
            'currency' => 'USD',
            'onboarding_completed' => true,
        ]);

        $this->service = new KnowledgeGapService();
    }

    #[Test] public function it_records_a_new_gap()
    {
        $gap = $this->service->record($this->organization->id, 'Do you deliver to Ikeja?', 'unanswered');

        $this->assertNotNull($gap->id);
        $this->assertSame('open', $gap->status);
        $this->assertSame(1, $gap->frequency);
    }

    #[Test] public function it_deduplicates_and_increments_frequency()
    {
        $this->service->record($this->organization->id, 'Do you deliver to Ikeja?', 'unanswered');
        $gap = $this->service->record($this->organization->id, 'Do you deliver to Ikeja?', 'unanswered');

        $this->assertSame(2, $gap->frequency);
        $this->assertSame(1, KnowledgeGap::count());
    }

    #[Test] public function it_orders_top_gaps_by_frequency()
    {
        $this->service->record($this->organization->id, 'Question A', 'unanswered');
        $this->service->record($this->organization->id, 'Question B', 'unanswered');
        $this->service->record($this->organization->id, 'Question B', 'unanswered');
        $this->service->record($this->organization->id, 'Question B', 'unanswered');

        $top = $this->service->top($this->organization->id);

        $this->assertSame('Question B', $top->first()->question);
        $this->assertSame(3, $top->first()->frequency);
    }

    #[Test] public function it_resolves_a_gap()
    {
        $gap = $this->service->record($this->organization->id, 'Question A', 'unanswered');

        $this->service->resolve($gap->id, $this->organization->id);

        $this->assertSame('resolved', $gap->fresh()->status);
    }
}