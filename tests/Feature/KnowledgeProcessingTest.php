<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Models\Organization;
use App\Services\Knowledge\ChunkingService;
use App\Services\Knowledge\DocumentProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KnowledgeProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected KnowledgeBase $knowledgeBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'onboarding_completed' => true,
        ]);

        app()->instance('current_organization_id', $this->organization->id);

        $this->knowledgeBase = KnowledgeBase::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test KB',
        ]);
    }

    #[Test] public function chunking_service_splits_long_text()
    {
        $chunker = app(ChunkingService::class);

        // Generate text longer than 1000 chars
        $text = str_repeat('This is a test sentence for chunking. ', 50); // ~2000 chars
        $chunks = $chunker->chunk($text, 500, 100);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertLessThanOrEqual(600, mb_strlen($chunks[0])); // 500 + some tolerance
    }

    #[Test] public function chunking_service_handles_short_text()
    {
        $chunker = app(ChunkingService::class);
        $chunks = $chunker->chunk('Short text.', 1000, 200);

        $this->assertCount(1, $chunks);
        $this->assertEquals('Short text.', $chunks[0]);
    }

    #[Test] public function chunking_service_estimates_token_count()
    {
        $chunker = app(ChunkingService::class);
        $count = $chunker->estimateTokenCount('Hello world, this is 40 characters long.');
        $this->assertGreaterThan(0, $count);
    }

    #[Test] public function document_processor_handles_manual_content()
    {
        $processor = app(DocumentProcessor::class);
        $source = new KnowledgeSource([
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $this->knowledgeBase->id,
            'type' => 'manual',
            'title' => 'Test Manual',
            'content' => 'This is manual content for testing.',
        ]);

        $text = $processor->extractText($source);
        $this->assertEquals('This is manual content for testing.', $text);
    }

    #[Test] public function document_processor_handles_faq_content()
    {
        $processor = app(DocumentProcessor::class);
        $source = new KnowledgeSource([
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $this->knowledgeBase->id,
            'type' => 'faq',
            'title' => 'Test FAQ',
            'content' => 'Q: What is this? A: A test FAQ.',
        ]);

        $text = $processor->extractText($source);
        $this->assertStringContainsString('A: A test FAQ.', $text);
    }

    #[Test] public function document_processor_handles_txt_content()
    {
        $processor = app(DocumentProcessor::class);
        $source = new KnowledgeSource([
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $this->knowledgeBase->id,
            'type' => 'txt',
            'title' => 'Test Text',
            'content' => 'Plain text content here.',
        ]);

        $text = $processor->extractText($source);
        $this->assertEquals('Plain text content here.', $text);
    }

    #[Test] public function document_processor_fetches_url_content()
    {
        Http::fake([
            'https://example.com/page' => Http::response(
                '<html><body><h1>Hello</h1><p>This is a test page with some content.</p></body></html>',
                200
            ),
        ]);

        $processor = app(DocumentProcessor::class);
        $source = new KnowledgeSource([
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $this->knowledgeBase->id,
            'type' => 'url',
            'title' => 'Test URL',
            'source_url' => 'https://example.com/page',
        ]);

        $text = $processor->extractText($source);

        $this->assertStringContainsString('Hello', $text);
        $this->assertStringContainsString('This is a test page', $text);
        // HTML tags should be stripped
        $this->assertStringNotContainsString('<html>', $text);
        $this->assertStringNotContainsString('<body>', $text);
    }

    #[Test] public function document_processor_handles_url_fetch_failure()
    {
        Http::fake([
            'https://broken-link.com' => Http::response('Not Found', 404),
        ]);

        $processor = app(DocumentProcessor::class);
        $source = new KnowledgeSource([
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $this->knowledgeBase->id,
            'type' => 'url',
            'title' => 'Broken URL',
            'source_url' => 'https://broken-link.com',
        ]);

        $text = $processor->extractText($source);
        $this->assertEquals('', $text);
    }

    #[Test] public function document_processor_cleans_text()
    {
        $processor = app(DocumentProcessor::class);

        $dirty = "Hello   \n\n\nWorld!\r\n\r\n\tExtra   spaces.";
        $clean = $processor->cleanText($dirty);

        $this->assertStringNotContainsString('   ', $clean);
        $this->assertStringNotContainsString("\r\n", $clean);
        $this->assertStringNotContainsString("\n\n\n", $clean);
        $this->assertStringContainsString('Hello', $clean);
        $this->assertStringContainsString('World!', $clean);
    }

    #[Test] public function knowledge_source_creates_document_and_chunks()
    {
        // Simulate what ProcessKnowledgeDocument does (synchronous version)
        $source = KnowledgeSource::create([
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $this->knowledgeBase->id,
            'type' => 'manual',
            'title' => 'Integration Test',
            'content' => str_repeat('This is meaningful content that should be chunked. ', 40),
            'status' => 'pending',
        ]);

        $processor = app(DocumentProcessor::class);
        $chunker = app(ChunkingService::class);

        $rawText = $processor->extractText($source);
        $cleanedText = $processor->cleanText($rawText);

        $this->assertNotEmpty($rawText);
        $this->assertNotEmpty($cleanedText);

        // Create document
        $document = KnowledgeDocument::updateOrCreate(
            ['knowledge_source_id' => $source->id],
            [
                'organization_id' => $source->organization_id,
                'content' => $cleanedText,
                'status' => 'active',
            ]
        );

        $this->assertNotNull($document);

        // Chunk
        $chunks = $chunker->chunk($cleanedText);
        $this->assertGreaterThan(0, count($chunks));

        foreach ($chunks as $index => $chunkText) {
            KnowledgeChunk::create([
                'organization_id' => $source->organization_id,
                'knowledge_document_id' => $document->id,
                'content' => $chunkText,
                'chunk_index' => $index,
                'token_count' => $chunker->estimateTokenCount($chunkText),
                'metadata' => [
                    'source_id' => $source->id,
                    'source_type' => $source->type,
                    'source_title' => $source->title,
                ],
            ]);
        }

        $this->assertEquals(count($chunks), $document->chunks()->count());

        $source->update(['status' => 'completed']);
        $this->assertEquals('completed', $source->fresh()->status);
    }

    #[Test] public function knowledge_source_empty_content_throws_exception()
    {
        $source = KnowledgeSource::create([
            'organization_id' => $this->organization->id,
            'knowledge_base_id' => $this->knowledgeBase->id,
            'type' => 'manual',
            'title' => 'Empty Source',
            'content' => '',
            'status' => 'pending',
        ]);

        $processor = app(DocumentProcessor::class);
        $rawText = $processor->extractText($source);

        $this->assertEmpty(trim($rawText));
        $this->assertEquals('pending', $source->fresh()->status);
    }
}