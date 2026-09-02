<?php

namespace App\Services\Knowledge;

use App\Ai\Providers\AiProviderInterface;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KnowledgeRagService
{
    public function __construct(
        protected AiProviderInterface $aiProvider,
        protected ChunkingService $chunker,
    ) {}

    /**
     * Search the knowledge base for relevant chunks given a user query.
     *
     * @param int $organizationId
     * @param string $query
     * @param int $topK
     * @param array|null $knowledgeBaseIds Filter by specific knowledge base IDs (null = all)
     * @return array{chunks: array, query_embedding: array|null}
     */
    public function search(int $organizationId, string $query, int $topK = 5, ?array $knowledgeBaseIds = null): array
    {
        try {
            // Generate query embedding
            $embeddings = $this->aiProvider->embed($query);
            if (empty($embeddings) || empty($embeddings[0])) {
                return $this->fallbackKeywordSearch($organizationId, $query, $topK, $knowledgeBaseIds);
            }

            $queryEmbedding = $embeddings[0];

            // Get all chunks with embeddings for this organization
            $chunksQuery = KnowledgeChunk::where('organization_id', $organizationId)
                ->whereNotNull('embedding');
            
            // Filter by knowledge base IDs if specified
            if ($knowledgeBaseIds !== null && !empty($knowledgeBaseIds)) {
                $chunksQuery->whereHas('document.source', function ($q) use ($knowledgeBaseIds) {
                    $q->whereIn('knowledge_base_id', $knowledgeBaseIds);
                });
            }
            
            $chunks = $chunksQuery->get();

            if ($chunks->isEmpty()) {
                return $this->fallbackKeywordSearch($organizationId, $query, $topK);
            }

            // Calculate cosine similarity
            $scored = [];
            foreach ($chunks as $chunk) {
                $chunkEmbedding = json_decode($chunk->embedding, true);
                if (! $chunkEmbedding) continue;

                $similarity = $this->cosineSimilarity($queryEmbedding, $chunkEmbedding);

                $scored[] = [
                    'chunk' => $chunk,
                    'score' => $similarity,
                ];
            }

            // Sort by similarity descending
            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

            // Take top K
            $top = array_slice($scored, 0, $topK);

            $results = [];
            foreach ($top as $item) {
                $chunk = $item['chunk'];
                $results[] = [
                    'content' => $chunk->content,
                    'score' => round($item['score'], 4),
                    'source_title' => $chunk->metadata['source_title'] ?? 'Unknown',
                    'source_type' => $chunk->metadata['source_type'] ?? 'unknown',
                    'chunk_id' => $chunk->id,
                    'document_id' => $chunk->knowledge_document_id,
                ];
            }

            return [
                'chunks' => $results,
                'query_embedding' => $queryEmbedding,
            ];

        } catch (\Throwable $e) {
            Log::error('RAG search failed', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return $this->fallbackKeywordSearch($organizationId, $query, $topK);
        }
    }

    /**
     * Fallback: keyword-based search when embeddings are unavailable.
     */
    protected function fallbackKeywordSearch(int $organizationId, string $query, int $topK, ?array $knowledgeBaseIds = null): array
    {
        $terms = explode(' ', strtolower($query));
        $terms = array_filter($terms, fn ($t) => strlen($t) > 2);

        if (empty($terms)) {
            return ['chunks' => [], 'query_embedding' => null];
        }

        $chunksQuery = KnowledgeChunk::where('organization_id', $organizationId)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('content', 'like', "%{$term}%");
                }
            });
        
        // Filter by knowledge base IDs if specified
        if ($knowledgeBaseIds !== null && !empty($knowledgeBaseIds)) {
            $chunksQuery->whereHas('document.source', function ($q) use ($knowledgeBaseIds) {
                $q->whereIn('knowledge_base_id', $knowledgeBaseIds);
            });
        }
        
        $chunks = $chunksQuery->take($topK * 3)->get();

        // Score chunks by term frequency for relevance
        $scored = [];
        foreach ($chunks as $chunk) {
            $content = strtolower($chunk->content);
            $score = 0;
            foreach ($terms as $term) {
                $score += substr_count($content, $term);
            }
            // Bonus for exact phrase match
            if (str_contains($content, strtolower($query))) {
                $score += 3;
            }
            $scored[] = [
                'chunk' => $chunk,
                'raw_score' => $score,
                'score' => min(0.9, 0.3 + ($score * 0.1)),
            ];
        }

        usort($scored, fn ($a, $b) => $b['raw_score'] <=> $a['raw_score']);

        $results = [];
        foreach (array_slice($scored, 0, $topK) as $item) {
            $chunk = $item['chunk'];
            $results[] = [
                'content' => $chunk->content,
                'score' => round($item['score'], 4),
                'source_title' => $chunk->metadata['source_title'] ?? 'Unknown',
                'source_type' => $chunk->metadata['source_type'] ?? 'unknown',
                'chunk_id' => $chunk->id,
                'document_id' => $chunk->knowledge_document_id,
            ];
        }

        return [
            'chunks' => $results,
            'query_embedding' => null,
        ];
    }

    /**
     * Calculate cosine similarity between two vectors.
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0;
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Build a context string from retrieved chunks for inclusion in AI prompt.
     */
    public function buildContext(array $searchResults, int $maxTokens = 3000): string
    {
        $chunks = $searchResults['chunks'] ?? [];

        if (empty($chunks)) {
            return '';
        }

        $contextText = "RELEVANT KNOWLEDGE (from company documentation):\n\n";
        $totalTokens = 0;

        foreach ($chunks as $item) {
            $chunk = "--- From: {$item['source_title']} (relevance: {$item['score']}) ---\n{$item['content']}\n\n";
            $tokenEstimate = $this->chunker->estimateTokenCount($chunk);

            if ($totalTokens + $tokenEstimate > $maxTokens) {
                break;
            }

            $contextText .= $chunk;
            $totalTokens += $tokenEstimate;
        }

        return $contextText;
    }
}