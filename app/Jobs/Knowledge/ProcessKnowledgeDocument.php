<?php

namespace App\Jobs\Knowledge;

use App\Jobs\Traits\TenantAwareJob;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeSource;
use App\Services\Knowledge\ChunkingService;
use App\Services\Knowledge\DocumentProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessKnowledgeDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    public int $timeout = 600; // 10 minutes
    public int $tries = 3;

    public function __construct(
        protected int $sourceId,
    ) {
        // Auto-set tenant from the source's organization for convenience
        $source = KnowledgeSource::withoutTenancy()->find($sourceId);
        if ($source) {
            $this->tenantOrganizationId = $source->organization_id;
        }
    }

    public function handle(
        DocumentProcessor $processor,
        ChunkingService $chunker,
    ): void {
        $source = KnowledgeSource::with('knowledgeBase')->find($this->sourceId);

        if (! $source) {
            Log::warning('Knowledge source not found', ['source_id' => $this->sourceId]);
            return;
        }

        try {
            // Update status
            $source->update(['status' => 'processing', 'progress' => 10]);

            // 1. Extract text
            $rawText = $processor->extractText($source);
            if (empty($rawText)) {
                throw new \Exception('No text could be extracted from the document.');
            }

            $source->update(['progress' => 30]);

            // 2. Clean text
            $cleanedText = $processor->cleanText($rawText);
            $source->update(['progress' => 50]);

            // 3. Create/update knowledge document
            $document = KnowledgeDocument::updateOrCreate(
                ['knowledge_source_id' => $source->id],
                [
                    'organization_id' => $source->organization_id,
                    'content' => $cleanedText,
                    'status' => 'active',
                ]
            );

            // 4. Chunk the text
            $chunks = $chunker->chunk($cleanedText);
            $source->update(['progress' => 70]);

            // 5. Delete old chunks and create new ones
            $document->chunks()->delete();

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

            // 6. Generate embeddings
            $source->update(['progress' => 85]);
            $this->generateEmbeddings($document, $source);

            $source->update([
                'chunk_count' => $document->chunks()->count(),
            ]);

            $document->update(['chunk_count' => $document->chunks()->count()]);

            // 7. Complete
            $source->update([
                'status' => 'completed',
                'progress' => 100,
            ]);

            Log::info('Knowledge document processed', [
                'source_id' => $source->id,
                'chunks' => count($chunks),
            ]);

        } catch (\Exception $e) {
            Log::error('Knowledge processing failed', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            $source->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'progress' => 0,
            ]);

            if ($this->attempts() >= $this->tries) {
                $source->update(['status' => 'failed']);
            }
        }
    }

    /**
     * Generate embeddings for all chunks.
     */
    protected function generateEmbeddings(KnowledgeDocument $document, KnowledgeSource $source): void
    {
        try {
            $provider = app(\App\Ai\Providers\AiProviderInterface::class);

            $chunks = $document->chunks()
                ->whereNull('embedding')
                ->get();

            if ($chunks->isEmpty()) return;

            // Process in batches of 10
            foreach ($chunks->chunk(10) as $batch) {
                $texts = $batch->pluck('content')->toArray();
                $embeddings = $provider->embed($texts);

                foreach ($batch as $i => $chunk) {
                    if (isset($embeddings[$i])) {
                        $chunk->update(['embedding' => json_encode($embeddings[$i])]);
                    }
                }
            }

            Log::info('Embeddings generated', [
                'document_id' => $document->id,
                'chunks_processed' => $chunks->count(),
            ]);

        } catch (\Exception $e) {
            Log::warning('Embedding generation failed (non-fatal)', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            // Non-fatal: chunks exist without embeddings, can be regenerated
        }
    }
}