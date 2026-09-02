<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\Automation\AutomationService;
use App\Services\Crm\LeadScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function __construct(
        protected LeadScoringService $scoringService,
        protected AutomationService $automationService,
    ) {}

    public function index(Request $request)
    {
        $organization = current_org();
        $leads = $organization->leads()->with(['customer', 'aiEmployee'])
            ->when($request->stage, fn ($q) => $q->where('stage', $request->stage))
            ->when($request->search, fn ($q) => $q->whereHas('customer', fn ($q) => $q->where('first_name', 'like', "%{$request->search}%")->orWhere('last_name', 'like', "%{$request->search}%")))
            ->latest()->paginate(20)->withQueryString();
        $pipeline = ['new' => $organization->leads()->where('stage', 'new')->count(),'contacted' => $organization->leads()->where('stage', 'contacted')->count(),'qualified' => $organization->leads()->where('stage', 'qualified')->count(),'proposal' => $organization->leads()->where('stage', 'proposal')->count(),'won' => $organization->leads()->where('stage', 'won')->count(),'lost' => $organization->leads()->where('stage', 'lost')->count()];
        return Inertia::render('Leads/Index', ['leads' => $leads, 'pipeline' => $pipeline, 'filters' => $request->only(['search', 'stage'])]);
    }

    public function show(Lead $lead)
    {
        $orgId = current_org_id(); if ($lead->organization_id !== $orgId) abort(403);
        $lead->load(['customer', 'aiEmployee', 'activities', 'conversation']);
        return Inertia::render('Leads/Show', ['lead' => $lead]);
    }

    public function update(Request $request, Lead $lead)
    {
        $orgId = current_org_id(); if ($lead->organization_id !== $orgId) abort(403);
        $validated = $request->validate(['stage' => 'nullable|string|in:new,contacted,qualified,proposal,won,lost','product_interest' => 'nullable|string','notes' => 'nullable|string','estimated_value' => 'nullable|numeric']);
        $oldStage = $lead->stage; $lead->update($validated);
        if (isset($validated['stage']) && $validated['stage'] !== $oldStage) {
            $lead->activities()->create(['organization_id' => $orgId, 'type' => 'status_change', 'description' => "Lead moved from '{$oldStage}' to '{$validated['stage']}'", 'performed_by' => auth()->id()]);

            // Fire automation trigger for lead stage change
            try {
                $this->automationService->triggerOnLeadStageChanged($lead, $oldStage, $validated['stage']);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Automation trigger failed in LeadController', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        if ($lead->customer) $this->scoringService->updateScore($lead->customer);
        return back()->with('success', 'Lead updated.');
    }
}