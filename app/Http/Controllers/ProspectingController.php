<?php

namespace App\Http\Controllers;

use App\Jobs\Prospecting\QualifyCampaignJob;
use App\Jobs\Prospecting\RunHuntJob;
use App\Jobs\Prospecting\RunOutreachJob;
use App\Models\ProspectingCampaign;
use App\Models\Prospect;
use App\Models\SuppressionList;
use App\Services\Prospecting\CampaignCreator;
use App\Services\Prospecting\OutreachService;
use App\Services\Prospecting\ProspectQualifierService;
use App\Services\Prospecting\ProspectingSettingsService;
use App\Services\Prospecting\SuppressionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Tenant-facing outbound prospecting (the AI Sales Employee surface).
 *
 * Mirrors the Platform Owner ProspectingController but scoped to the
 * currently-active organization via current_org_id().
 */
class ProspectingController extends Controller
{
    public function __construct(protected ProspectingSettingsService $settings) {}

    public function index()
    {
        $orgId = $this->currentOrganizationId();
        if (! $orgId) {
            return redirect()->route('onboarding.show');
        }

        $stats = [
            'campaigns' => ProspectingCampaign::where('organization_id', $orgId)->count(),
            'active_campaigns' => ProspectingCampaign::where('organization_id', $orgId)->where('status', 'active')->count(),
            'prospects' => Prospect::where('organization_id', $orgId)->count(),
            'qualified' => Prospect::where('organization_id', $orgId)->where('status', 'qualified')->count(),
            'contacted' => Prospect::where('organization_id', $orgId)->where('status', 'contacted')->count(),
            'replied' => Prospect::where('organization_id', $orgId)->where('status', 'replied')->count(),
            'avg_score' => round((float) (Prospect::where('organization_id', $orgId)->where('score', '>', 0)->avg('score') ?? 0), 1),
        ];

        $recentReplies = Prospect::with('campaign')
            ->where('organization_id', $orgId)
            ->whereNotNull('replied_at')
            ->latest('replied_at')
            ->limit(10)
            ->get();

        $recentCampaigns = ProspectingCampaign::where('organization_id', $orgId)
            ->withCount('prospects')
            ->latest()
            ->limit(6)
            ->get();

        return Inertia::render('Prospecting/Index', [
            'stats' => $stats,
            'recentReplies' => $recentReplies,
            'recentCampaigns' => $recentCampaigns,
            'searchConfigured' => app(\App\Services\Prospecting\WebSearchService::class)->isConfigured($orgId),
        ]);
    }

    public function campaigns(Request $request)
    {
        $orgId = $this->currentOrganizationId();

        $employee = $request->employee
            ? \App\Models\AiEmployee::where('organization_id', $orgId)->find($request->employee)
            : null;

        $campaigns = ProspectingCampaign::where('organization_id', $orgId)
            ->when($request->employee, fn ($q) => $q->where('ai_employee_id', $request->employee))
            ->withCount('prospects')
            ->withCount(['prospects as qualified_count' => fn ($q) => $q->where('status', 'qualified')])
            ->withCount(['prospects as contacted_count' => fn ($q) => $q->where('status', 'contacted')])
            ->withCount(['prospects as replied_count' => fn ($q) => $q->where('status', 'replied')])
            ->latest()
            ->get();

        return Inertia::render('Prospecting/Campaigns', [
            'campaigns' => $campaigns,
            'personas' => \App\Models\BuyerPersona::where('organization_id', $orgId)
                ->orderBy('name')
                ->get(['id', 'name', 'avatar']),
            'defaultSender' => [
                'name' => $this->settings->senderName(),
                'email' => $this->settings->senderEmail(),
            ],
            'searchConfigured' => app(\App\Services\Prospecting\WebSearchService::class)->isConfigured($orgId),
            'employee' => $employee ? ['id' => $employee->id, 'name' => $employee->name] : null,
        ]);
    }

    public function storeCampaign(Request $request)
    {
        $organization = $this->currentOrganization();
        if (! $organization) {
            return redirect()->route('onboarding.show');
        }

        $validated = $this->validateCampaign($request);

        app(CampaignCreator::class)->create($validated, $organization);

        return back()->with('success', 'Prospecting campaign created.');
    }

    public function updateCampaign(Request $request, int $campaign)
    {
        $campaign = $this->campaignOrFail($campaign);
        $validated = $this->validateCampaign($request);

        app(CampaignCreator::class)->update($campaign, $validated);

        return back()->with('success', 'Prospecting campaign updated.');
    }

    public function destroyCampaign(int $campaign)
    {
        $this->campaignOrFail($campaign)->delete();

        return back()->with('success', 'Campaign deleted.');
    }

    public function toggleCampaign(int $campaign)
    {
        $campaign = $this->campaignOrFail($campaign);
        $campaign->update(['status' => $campaign->status === 'active' ? 'paused' : 'active']);

        return back()->with('success', $campaign->status === 'active' ? 'Campaign activated.' : 'Campaign paused.');
    }

    public function runHunt(int $campaign)
    {
        $campaign = $this->campaignOrFail($campaign);
        RunHuntJob::dispatch($campaign->id);

        $message = 'Hunt started. Prospects will appear shortly.';
        if (! app(\App\Services\Prospecting\WebSearchService::class)->isConfigured($campaign->organization_id)) {
            $message = 'Hunt started — web search isn\'t connected, so Nomdal will use AI-generated suggestions. Connect a search provider for live results.';
        }

        return back()->with('success', $message);
    }

    public function qualifyAll(int $campaign)
    {
        QualifyCampaignJob::dispatch($this->campaignOrFail($campaign)->id);

        return back()->with('success', 'Qualification run started.');
    }

    public function runOutreach(int $campaign)
    {
        RunOutreachJob::dispatch($this->campaignOrFail($campaign)->id);

        return back()->with('success', 'Outreach run started.');
    }

    public function prospects(Request $request)
    {
        $orgId = $this->currentOrganizationId();
        $campaigns = ProspectingCampaign::where('organization_id', $orgId)->orderBy('name')->get();

        $prospects = Prospect::with('campaign')
            ->where('organization_id', $orgId)
            ->when($request->campaign, fn ($q) => $q->where('campaign_id', $request->campaign))
            ->when($request->employee, fn ($q) => $q->whereHas('campaign', fn ($q) => $q->where('ai_employee_id', $request->employee)))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('min_score'), fn ($q) => $q->where('score', '>=', (int) $request->min_score))
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('company', 'like', "%{$request->search}%")))
            ->orderByDesc('score')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Prospecting/Prospects', [
            'prospects' => $prospects,
            'campaigns' => $campaigns,
            'statuses' => ['new', 'qualified', 'disqualified', 'contacted', 'replied', 'converted', 'bounced', 'unsubscribed'],
            'filters' => $request->only(['search', 'campaign', 'employee', 'status', 'min_score']),
        ]);
    }

    public function showProspect(int $prospect)
    {
        $prospect = $this->prospectOrFail($prospect);
        $prospect->load('campaign', 'messages');

        return Inertia::render('Prospecting/ProspectDetail', [
            'prospect' => $prospect,
        ]);
    }

    public function generateProspect(int $prospect, OutreachService $outreach)
    {
        $outreach->generate($this->prospectOrFail($prospect));

        return back()->with('success', '2-pass AI email generated. Review and send it below.');
    }

    public function sendProspect(int $prospect, OutreachService $outreach)
    {
        $result = $outreach->send($this->prospectOrFail($prospect), true);

        return empty($result['sent'])
            ? back()->with('error', $result['error'] ?? 'Could not send email.')
            : back()->with('success', 'Email sent.');
    }

    public function qualifyProspect(int $prospect, ProspectQualifierService $qualifier)
    {
        $result = $qualifier->qualify($this->prospectOrFail($prospect));

        return back()->with('success', "Prospect scored {$result['score']}/10 ({$result['status']}).");
    }

    /**
     * Research a single prospect and store evidence-backed "why good fit" notes.
     */
    public function researchProspect(int $prospect)
    {
        app(\App\Services\Prospecting\ProspectResearcherService::class)->research($this->prospectOrFail($prospect));

        return back()->with('success', 'Research complete.');
    }

    /**
     * Research all qualified prospects in a campaign (async).
     */
    public function researchQualified(int $campaign)
    {
        \App\Jobs\Prospecting\ResearchCampaignJob::dispatch($this->campaignOrFail($campaign)->id);

        return back()->with('success', 'Researching qualified prospects… results will appear shortly.');
    }

    /**
     * Send due follow-ups to contacted prospects who haven't replied.
     */
    public function runFollowups(int $campaign)
    {
        \App\Jobs\Prospecting\ProspectFollowupJob::dispatch($this->campaignOrFail($campaign)->id);

        return back()->with('success', 'Follow-ups queued for prospects who haven\'t replied.');
    }

    /**
     * Manually convert a prospect into an opportunity + quotation + invoice.
     */
    public function convertProspect(int $prospect)
    {
        $result = app(\App\Services\Prospecting\ProspectConversionService::class)->convert($this->prospectOrFail($prospect));

        if (! empty($result['converted'])) {
            return back()->with('success', 'Converted to an opportunity. Lead #' . $result['lead_id'] . ' created with a quotation and invoice.');
        }

        return back()->with('error', 'Could not convert this prospect.');
    }

    /**
     * Book a meeting with a prospect on the tenant's Google Calendar.
     */
    public function bookMeeting(int $prospect, Request $request)
    {
        $prospect = $this->prospectOrFail($prospect);

        $start = $request->input('start_time') ?: now()->addDay()->setTime(10, 0)->toIso8601String();
        $end = $request->input('end_time') ?: now()->addDay()->setTime(10, 30)->toIso8601String();

        $result = app(\App\Services\Mcp\CalendarMeetingService::class)->book(
            $prospect->organization_id,
            'Meeting with ' . ($prospect->name ?: ($prospect->company ?: 'prospect')),
            $start,
            $end,
            $prospect->email,
            'Intro call with ' . ($prospect->company ?: 'prospect'),
        );

        if (empty($result['success'])) {
            return back()->with('error', $result['error'] ?? 'Could not book the meeting.');
        }

        $prospect->update([
            'meeting_booked_at' => now(),
            'meeting_link' => $result['event_link'] ?? null,
            'intent' => 'interested',
        ]);

        return back()->with('success', 'Meeting booked on Google Calendar.');
    }

    public function suppressProspect(int $prospect, SuppressionService $suppression)
    {
        $prospect = $this->prospectOrFail($prospect);
        $suppression->suppress($prospect->email, 'dnc', $prospect->organization_id, $prospect->campaign_id, 'manual');
        $prospect->update(['status' => 'unsubscribed', 'suppressed_at' => now(), 'suppression_reason' => 'dnc']);
        $prospect->events()->create(['organization_id' => $prospect->organization_id, 'type' => 'suppressed', 'payload' => ['reason' => 'dnc']]);

        return back()->with('success', 'Prospect added to the do-not-contact list.');
    }

    public function suppression()
    {
        return Inertia::render('Prospecting/Suppression', [
            'entries' => SuppressionList::where('organization_id', $this->currentOrganizationId())->latest()->get(),
        ]);
    }

    /**
     * Tenant web-search settings (platform shared search or bring-your-own key).
     */
    public function settings()
    {
        $orgId = $this->currentOrganizationId();
        if (! $orgId) {
            return redirect()->route('onboarding.show');
        }

        $tenant = \App\Models\TenantSearchSettings::forOrganization($orgId);
        $platform = \App\Models\ProspectingSettings::instance();
        $platformAvailable = $platform->search_provider !== 'none' && ! empty($platform->search_api_key);

        return Inertia::render('Prospecting/Settings', [
            'settings' => [
                'provider' => $tenant->provider,
                'has_key' => $tenant->hasKey(),
                'using_platform' => $tenant->usesPlatform() && $platformAvailable,
                'platform_available' => $platformAvailable,
                'platform_provider' => $platform->search_provider,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $orgId = $this->currentOrganizationId();
        if (! $orgId) {
            return redirect()->route('onboarding.show');
        }

        $validated = $request->validate([
            'provider' => 'required|string|in:none,platform,serper,brave',
            'api_key' => 'nullable|string|max:500',
        ]);

        $tenant = \App\Models\TenantSearchSettings::forOrganization($orgId);

        // "none" and "platform" don't need a tenant-held API key.
        if (in_array($validated['provider'], ['none', 'platform'], true)) {
            $tenant->update(['provider' => $validated['provider'], 'api_key' => null]);

            return back()->with('success', $validated['provider'] === 'platform'
                ? 'Web search will use the shared platform search.'
                : 'Web search disabled. Nomdal will use AI-generated suggestions.');
        }

        // Bring-your-own key providers (Serper / Brave). Empty key = keep existing.
        $update = ['provider' => $validated['provider']];
        if (! empty(trim((string) $validated['api_key']))) {
            $update['api_key'] = trim($validated['api_key']);
        }

        $tenant->update($update);

        return back()->with('success', 'Web search connected. Your next hunt will find live prospects.');
    }

    protected function campaignOrFail(int $id): ProspectingCampaign
    {
        return ProspectingCampaign::where('organization_id', $this->currentOrganizationId())->findOrFail($id);
    }

    protected function prospectOrFail(int $id): Prospect
    {
        return Prospect::where('organization_id', $this->currentOrganizationId())->findOrFail($id);
    }

    protected function validateCampaign(Request $request): array
    {
        return $request->validate(app(CampaignCreator::class)->rules());
    }
}
