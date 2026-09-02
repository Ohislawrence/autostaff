<?php

namespace App\Services\Knowledge;

use App\Models\KnowledgeGap;

/**
 * Learning loop — captures signals the AI couldn't handle well and surfaces
 * them to the business owner as actionable "knowledge gaps".
 *
 * This is deliberately NOT automatic policy rewriting. Instead it aggregates
 * repeated unanswered questions, escalations, low-confidence interpretations,
 * and failed tool calls so the owner can add answers to the knowledge base.
 */
class KnowledgeGapService
{
    /**
     * Record (or increment) a knowledge gap.
     */
    public function record(
        int $organizationId,
        string $question,
        string $category,
        ?string $suggestedAnswer = null,
    ): KnowledgeGap {
        $normalized = mb_strtolower(trim($question));

        $gap = KnowledgeGap::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('status', 'open')
            ->whereRaw('LOWER(question) = ?', [$normalized])
            ->first();

        if ($gap) {
            $gap->increment('frequency');
            $gap->update(['last_seen_at' => now()]);
            return $gap;
        }

        return KnowledgeGap::create([
            'organization_id' => $organizationId,
            'category' => $category,
            'source' => 'automatic',
            'question' => trim($question),
            'frequency' => 1,
            'status' => 'open',
            'suggested_answer' => $suggestedAnswer,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Open gaps ordered by frequency (most impactful first).
     */
    public function top(int $organizationId, int $limit = 20): \Illuminate\Support\Collection
    {
        return KnowledgeGap::where('organization_id', $organizationId)
            ->where('status', 'open')
            ->orderByDesc('frequency')
            ->latest('last_seen_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Resolve a gap (owner supplied the answer).
     */
    public function resolve(int $gapId, int $organizationId): void
    {
        KnowledgeGap::where('id', $gapId)
            ->where('organization_id', $organizationId)
            ->update(['status' => 'resolved']);
    }
}