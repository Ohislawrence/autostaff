<?php

namespace App\Http\Controllers;

use App\Jobs\Knowledge\ProcessKnowledgeDocument;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeSource;
use App\Services\Knowledge\KnowledgeRagService;
use App\Services\Tenant\TenantStorageManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeController extends Controller
{
    public function __construct(
        protected KnowledgeRagService $ragService,
        protected TenantStorageManager $storage,
    ) {}

    public function index()
    {
        $organization = current_org();
        $knowledgeBases = $organization->knowledgeBases()->withCount('sources')->latest()->get();
        $recentSources = $organization->knowledgeSources()->with('knowledgeBase')->latest()->take(10)->get();
        return Inertia::render('Knowledge/Index', ['knowledgeBases' => $knowledgeBases, 'recentSources' => $recentSources]);
    }

    public function storeKnowledgeBase(Request $request)
    {
        $organization = current_org();
        $validated = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $kb = $organization->knowledgeBases()->create($validated);
        return back()->with('success', "Knowledge base '{$kb->name}' created.");
    }

    public function uploadSource(Request $request, KnowledgeBase $knowledgeBase)
    {
        $organization = current_org();

        // Check knowledge source limit
        $usageTracker = app(\App\Services\Billing\UsageTracker::class);
        if ($usageTracker->isAtLimit($organization, 'knowledge_sources')) {
            $upgradePlan = $usageTracker->getUpgradePlan($organization);
            $message = "You've reached your plan limit for Knowledge Sources.";
            if ($upgradePlan) {
                $message .= " Upgrade to {$upgradePlan['plan']->name} to add more.";
            }
            return back()->with('error', $message);
        }

        $request->validate(['type' => 'required|in:file,url,manual,faq','title' => 'required|string|max:255','file' => 'required_if:type,file|nullable|file|mimes:pdf,docx,txt,csv|max:20480','url' => 'required_if:type,url|nullable|url|max:2048','content' => 'required_if:type,manual,faq|nullable|string|max:100000']);
        $source = new KnowledgeSource(['organization_id' => $organization->id, 'knowledge_base_id' => $knowledgeBase->id, 'title' => $request->title, 'type' => $request->type, 'status' => 'pending']);
        if ($request->type === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $source->type = match ($extension) {'pdf' => 'pdf','docx' => 'docx','txt' => 'txt','csv' => 'csv',default => $extension};
            $source->file_path = $this->storage->store($organization->id, $file, 'knowledge');
        } elseif ($request->type === 'url') { $source->source_url = $request->url; }
        elseif (in_array($request->type, ['manual', 'faq'])) { $source->content = $request->content; }
        $source->save();

        // Process immediately (inline, not via queue)
        $this->processSourceNow($source);

        // Refresh and check status
        $source->refresh();
        if ($source->status === 'completed') {
            $chunks = $source->chunk_count ?? 0;
            return back()->with('success', "Source '{$source->title}' processed — {$chunks} chunks created.");
        } elseif ($source->status === 'failed') {
            return back()->with('error', "Processing failed: {$source->error_message}");
        }

        return back()->with('success', "Source '{$source->title}' saved.");
    }

    public function inspect(Request $request)
    {
        $organization = current_org();
        $request->validate(['query' => 'required|string|max:500']);
        
        try {
            $queryString = $request->input('query');
            $results = $this->ragService->search($organization->id, $queryString, 5);
            
            // Get total available chunks for this org
            $totalChunks = \App\Models\KnowledgeChunk::where('organization_id', $organization->id)->count();
            $chunksWithEmbeddings = \App\Models\KnowledgeChunk::where('organization_id', $organization->id)
                ->whereNotNull('embedding')
                ->count();
            
            return response()->json([
                'query' => $queryString, 
                'results' => $results['chunks'] ?? [], 
                'embedding_used' => $results['query_embedding'] !== null,
                'total_chunks' => $totalChunks,
                'chunks_with_embeddings' => $chunksWithEmbeddings,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Knowledge inspect error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'query' => $request->input('query'),
                'results' => [],
                'embedding_used' => false,
                'error' => 'Search failed: ' . $e->getMessage(),
                'total_chunks' => 0,
                'chunks_with_embeddings' => 0,
            ], 500);
        }
    }

    /**
     * Display the tenant file browser.
     */
    public function files(Request $request)
    {
        $organization = current_org();
        $category = $request->query('category');
        $files = $this->storage->listFiles($organization->id, $category);
        $totalUsage = $this->storage->totalUsage($organization->id);

        // Get unique categories for filter
        $categories = array_unique(array_map(fn ($f) => $f['category'], $files));
        sort($categories);

        return Inertia::render('Files/Index', [
            'files' => $files,
            'categories' => array_values($categories),
            'currentCategory' => $category,
            'totalUsage' => $totalUsage,
            'totalUsageFormatted' => TenantStorageManager::formatBytes($totalUsage),
        ]);
    }

    /**
     * Download a tenant file.
     */
    public function downloadFile(Request $request)
    {
        $organization = current_org();
        $path = $request->query('path');

        if (! $path) {
            abort(400, 'File path is required.');
        }

        // Security: ensure file belongs to this tenant
        $tenantPrefix = $this->storage->tenantPath($organization->id) . '/';
        if (! str_starts_with($path, $tenantPrefix)) {
            abort(403, 'Access denied.');
        }

        if (! $this->storage->exists($path)) {
            abort(404, 'File not found.');
        }

        return $this->storage->download($path);
    }

    /**
     * Process a knowledge source inline (synchronous).
     */
    protected function processSourceNow(KnowledgeSource $source): void
    {
        $processor = app(\App\Services\Knowledge\DocumentProcessor::class);
        $chunker = app(\App\Services\Knowledge\ChunkingService::class);

        try {
            $source->update(['status' => 'processing', 'progress' => 10]);

            // 1. Extract text
            $rawText = $processor->extractText($source);
            if (empty(trim($rawText))) {
                throw new \Exception('No text could be extracted from the document.');
            }

            $source->update(['progress' => 30]);

            // 2. Clean text
            $cleanedText = $processor->cleanText($rawText);
            $source->update(['progress' => 50]);

            // 3. Create/update knowledge document
            $document = \App\Models\KnowledgeDocument::updateOrCreate(
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
                \App\Models\KnowledgeChunk::create([
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

            $source->update([
                'progress' => 85,
            ]);

            // 6. Generate embeddings
            $this->generateEmbeddingsInline($document, $source);

            // 7. Complete
            $document->update(['chunk_count' => $document->chunks()->count()]);

            $source->update([
                'status' => 'completed',
                'progress' => 100,
                'chunk_count' => $document->chunks()->count(),
            ]);

        } catch (\Exception $e) {
            // Truncate error message to fit text column (65KB) and avoid cascading truncation errors
            $errorMsg = mb_substr($e->getMessage(), 0, 5000);
            // Use DB facade to avoid dirty attribute issues if a prior update failed
            \Illuminate\Support\Facades\DB::table('knowledge_sources')
                ->where('id', $source->id)
                ->update([
                    'status' => 'failed',
                    'error_message' => $errorMsg,
                    'progress' => 0,
                    'updated_at' => now(),
                ]);

            \Illuminate\Support\Facades\Log::error('Knowledge processing failed inline', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate embeddings for all chunks in a document.
     */
    protected function generateEmbeddingsInline(\App\Models\KnowledgeDocument $document, KnowledgeSource $source): void
    {
        try {
            $provider = app(\App\Ai\Providers\AiProviderInterface::class);

            $chunks = $document->chunks()
                ->whereNull('embedding')
                ->get();

            if ($chunks->isEmpty()) {
                return;
            }

            // Process in batches of 10
            foreach ($chunks->chunk(10) as $batch) {
                $texts = $batch->pluck('content')->toArray();
                $embeddings = $provider->embed($texts);

                if (empty($embeddings)) {
                    \Illuminate\Support\Facades\Log::warning('Embedding generation returned empty results', [
                        'document_id' => $document->id,
                        'batch_size' => count($texts),
                    ]);
                    continue;
                }

                foreach ($batch as $i => $chunk) {
                    if (isset($embeddings[$i]) && is_array($embeddings[$i]) && !empty($embeddings[$i])) {
                        $chunk->update(['embedding' => json_encode($embeddings[$i])]);
                    }
                }
            }

            \Illuminate\Support\Facades\Log::info('Embeddings generated successfully', [
                'document_id' => $document->id,
                'chunks_processed' => $chunks->count(),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Embedding generation failed (non-fatal)', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            // Non-fatal: chunks exist without embeddings, can fall back to keyword search
        }
    }

    public function destroyKnowledgeBase(KnowledgeBase $knowledgeBase)
    {
        $orgId = current_org_id();
        if ($knowledgeBase->organization_id !== $orgId) abort(403);

        $sourceCount = $knowledgeBase->sources()->count();

        // Delete all sources in this knowledge base (cascades to documents, chunks)
        foreach ($knowledgeBase->sources as $source) {
            $doc = $source->document;
            if ($doc) {
                $doc->chunks()->delete();
                $doc->delete();
            }
            if ($source->file_path) {
                $this->storage->delete($source->file_path);
            }
            $source->delete();
        }

        $knowledgeBase->delete();

        return back()->with('success', "Knowledge base '{$knowledgeBase->name}' and {$sourceCount} source(s) deleted.");
    }

    public function destroySource(KnowledgeSource $knowledgeSource)
    {
        $orgId = current_org_id(); if ($knowledgeSource->organization_id !== $orgId) abort(403);
        $doc = $knowledgeSource->document; if ($doc) { $doc->chunks()->delete(); $doc->delete(); }
        if ($knowledgeSource->file_path) $this->storage->delete($knowledgeSource->file_path);
        $knowledgeSource->delete();
        return back()->with('success', 'Source deleted.');
    }
}