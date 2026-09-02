<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeGap;
use App\Services\Knowledge\KnowledgeGapService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeGapController extends Controller
{
    public function __construct(protected KnowledgeGapService $gapService) {}

    /**
     * Surface open knowledge gaps to the business owner.
     */
    public function index()
    {
        $organization = current_org();

        $gaps = $this->gapService->top($organization->id, 50)
            ->map(fn ($g) => [
                'id' => $g->id,
                'category' => $g->category,
                'question' => $g->question,
                'frequency' => $g->frequency,
                'suggested_answer' => $g->suggested_answer,
                'last_seen_at' => $g->last_seen_at?->diffForHumans(),
            ])
            ->values();

        return Inertia::render('KnowledgeGaps/Index', [
            'gaps' => $gaps,
        ]);
    }

    /**
     * Owner resolves a gap by acknowledging/adding an answer.
     */
    public function resolve(Request $request, KnowledgeGap $knowledgeGap)
    {
        abort_if($knowledgeGap->organization_id !== current_org_id(), 403);

        $knowledgeGap->update([
            'status' => 'resolved',
            'suggested_answer' => $request->input('suggested_answer', $knowledgeGap->suggested_answer),
        ]);

        return back()->with('success', 'Knowledge gap resolved.');
    }
}