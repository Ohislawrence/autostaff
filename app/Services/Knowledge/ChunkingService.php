<?php

namespace App\Services\Knowledge;

class ChunkingService
{
    protected int $defaultChunkSize = 1000;
    protected int $defaultChunkOverlap = 200;

    /**
     * Split text into chunks with overlap for better retrieval.
     */
    public function chunk(string $text, ?int $chunkSize = null, ?int $chunkOverlap = null): array
    {
        $chunkSize = $chunkSize ?? $this->defaultChunkSize;
        $chunkOverlap = $chunkOverlap ?? $this->defaultChunkOverlap;

        // Normalize text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $sentences = $this->splitIntoSentences($text);
        $currentChunk = '';
        $currentLength = 0;

        foreach ($sentences as $sentence) {
            $sentenceLength = mb_strlen($sentence);

            // If adding this sentence would exceed chunk size, save current and start new
            if ($currentLength + $sentenceLength > $chunkSize && $currentLength > 0) {
                $chunks[] = trim($currentChunk);

                // Overlap: keep last portion of previous chunk
                $overlapText = mb_substr($currentChunk, -(int) ($chunkOverlap / 2));
                $currentChunk = $overlapText . ' ' . $sentence;
                $currentLength = mb_strlen($currentChunk);
            } else {
                $currentChunk .= ' ' . $sentence;
                $currentLength += $sentenceLength + 1;
            }
        }

        // Don't forget the last chunk
        if (trim($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Split text into sentences.
     */
    protected function splitIntoSentences(string $text): array
    {
        // Split on sentence boundaries: ., !, ?, followed by space or newline
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // If no sentences found (e.g., no punctuation), split by newlines or just return as single
        if (empty($sentences) || count($sentences) === 1) {
            $sentences = preg_split('/\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        }

        // If still empty, return the whole text
        if (empty($sentences)) {
            return [$text];
        }

        return $sentences;
    }

    /**
     * Estimate token count (rough approximation: ~4 chars per token).
     */
    public function estimateTokenCount(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    /**
     * Set default chunk size.
     */
    public function setDefaultChunkSize(int $size): void
    {
        $this->defaultChunkSize = max(100, $size);
    }

    /**
     * Set default chunk overlap.
     */
    public function setDefaultChunkOverlap(int $overlap): void
    {
        $this->defaultChunkOverlap = max(0, min($overlap, $this->defaultChunkSize / 2));
    }
}