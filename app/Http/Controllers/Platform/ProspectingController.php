<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Jobs\Prospecting\QualifyCampaignJob;
use App\Jobs\Prospecting\RunHuntJob;
use App\Jobs\Prospecting\RunOutreachJob;
use App\Models\ProspectingCampaign;
use App\Models\Prospect;
use App\Services\Prospecting\ContactValidator;
use App\Services\Prospecting\OutreachService;
use App\Services\Prospecting\ProspectQualifierService;
use App\Services\Prospecting\ProspectingSettingsService;
use App\Services\Prospecting\ReplyAlertService;
use App\Services\Prospecting\SuppressionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProspectingController extends Controller
{
    public function __construct(protected ProspectingSettingsService $settings) {}

    public function dashboard()
    {
        $stats = [
            'campaigns' => ProspectingCampaign::count(),
            'active_campaigns' => ProspectingCampaign::where('status', 'active')->count(),
            'prospects' => Prospect::count(),
            'qualified' => Prospect::where('status', 'qualified')->count(),
            'contacted' => Prospect::where('status', 'contacted')->count(),
            'replied' => Prospect::where('status', 'replied')->count(),
            'avg_score' => round((float) (Prospect::where('score', '>', 0)->avg('score') ?? 0), 1),
        ];

        $recentReplies = Prospect::with('campaign')
            ->whereNotNull('replied_at')
            ->latest('replied_at')
            ->limit(10)
            ->get();

        $recentCampaigns = ProspectingCampaign::withCount('prospects')->latest()->limit(6)->get();

        return Inertia::render('Platform/Prospecting/Dashboard', [
            'stats' => $stats,
            'recentReplies' => $recentReplies,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }

    public function campaigns()
    {
        $campaigns = ProspectingCampaign::withCount('prospects')
            ->withCount(['prospects as qualified_count' => fn ($q) => $q->where('status', 'qualified')])
            ->withCount(['prospects as contacted_count' => fn ($q) => $q->where('status', 'contacted')])
            ->withCount(['prospects as replied_count' => fn ($q) => $q->where('status', 'replied')])
            ->latest()
            ->get();

        return Inertia::render('Platform/Prospecting/Campaigns', [
            'campaigns' => $campaigns,
            'defaultSender' => [
                'name' => $this->settings->senderName(),
                'email' => $this->settings->senderEmail(),
            ],
        ]);
    }

    public function storeCampaign(Request $request)
    {
        $validated = $this->validateCampaign($request);

        ProspectingCampaign::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'offer' => $validated['offer'] ?? null,
            'tone' => $validated['tone'] ?? 'professional',
            'sender_name' => $validated['sender_name'] ?? null,
            'sender_email' => $validated['sender_email'] ?? null,
            'daily_limit' => $validated['daily_limit'] ?? 25,
            'auto_outreach' => (bool) ($validated['auto_outreach'] ?? false),
            'icp' => $this->buildIcp($request),
            'postal_address' => $validated['postal_address'] ?? null,
            'from_domain' => $validated['from_domain'] ?? null,
            'compliance_regions' => $this->split($request->input('compliance_regions')),
            'sourcing_rules' => [
                'blocked_sources' => $this->split($request->input('blocked_sources')),
                'blocked_regions' => $this->split($request->input('blocked_regions')),
                'allow_ai_generated' => (bool) $request->input('allow_ai_generated', true),
            ],
            'max_per_hour' => (int) ($validated['max_per_hour'] ?? 50),
            'require_approval_ai_contacts' => (bool) ($validated['require_approval_ai_contacts'] ?? false),
            'status' => 'draft',
        ]);

        return back()->with('success', 'Prospecting campaign created.');
    }

    public function updateCampaign(Request $request, ProspectingCampaign $campaign)
    {
        $validated = $this->validateCampaign($request);

        $campaign->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'offer' => $validated['offer'] ?? null,
            'tone' => $validated['tone'] ?? 'professional',
            'sender_name' => $validated['sender_name'] ?? null,
            'sender_email' => $validated['sender_email'] ?? null,
            'daily_limit' => $validated['daily_limit'] ?? 25,
            'auto_outreach' => (bool) ($validated['auto_outreach'] ?? false),
            'icp' => $this->buildIcp($request),
            'postal_address' => $validated['postal_address'] ?? null,
            'from_domain' => $validated['from_domain'] ?? null,
            'compliance_regions' => $this->split($request->input('compliance_regions')),
            'sourcing_rules' => [
                'blocked_sources' => $this->split($request->input('blocked_sources')),
                'blocked_regions' => $this->split($request->input('blocked_regions')),
                'allow_ai_generated' => (bool) $request->input('allow_ai_generated', true),
            ],
            'max_per_hour' => (int) ($validated['max_per_hour'] ?? 50),
            'require_approval_ai_contacts' => (bool) ($validated['require_approval_ai_contacts'] ?? false),
        ]);

        return back()->with('success', 'Prospecting campaign updated.');
    }

    public function destroyCampaign(ProspectingCampaign $campaign)
    {
        $campaign->delete();

        return back()->with('success', 'Campaign deleted.');
    }

    public function toggleCampaign(ProspectingCampaign $campaign)
    {
        $campaign->update(['status' => $campaign->status === 'active' ? 'paused' : 'active']);

        return back()->with('success', $campaign->status === 'active' ? 'Campaign activated.' : 'Campaign paused.');
    }

    public function runHunt(ProspectingCampaign $campaign)
    {
        RunHuntJob::dispatch($campaign->id);

        return back()->with('success', 'Hunt started. Prospects will appear shortly.');
    }

    public function qualifyAll(ProspectingCampaign $campaign)
    {
        QualifyCampaignJob::dispatch($campaign->id);

        return back()->with('success', 'Qualification run started.');
    }

    public function runOutreach(ProspectingCampaign $campaign)
    {
        RunOutreachJob::dispatch($campaign->id);

        return back()->with('success', 'Outreach run started.');
    }

    public function prospects(Request $request)
    {
        $campaigns = ProspectingCampaign::orderBy('name')->get();

        $prospects = Prospect::with('campaign')
            ->when($request->campaign, fn ($q) => $q->where('campaign_id', $request->campaign))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('min_score'), fn ($q) => $q->where('score', '>=', (int) $request->min_score))
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('company', 'like', "%{$request->search}%")
                ->orWhere('title', 'like', "%{$request->search}%")))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Platform/Prospecting/Prospects', [
            'prospects' => $prospects,
            'campaigns' => $campaigns,
            'statuses' => ['new', 'qualified', 'disqualified', 'contacted', 'replied', 'converted', 'bounced', 'unsubscribed'],
            'filters' => $request->only(['search', 'campaign', 'status', 'min_score']),
        ]);
    }

    public function showProspect(Prospect $prospect)
    {
        $prospect->load(['campaign', 'messages']);

        return Inertia::render('Platform/Prospecting/ProspectDetail', [
            'prospect' => $prospect,
        ]);
    }

    public function storeProspect(Request $request, ProspectingCampaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'company_size' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|string|max:255',
        ]);

        $campaign->prospects()->create(array_merge($validated, ['source' => 'manual', 'status' => 'new']));

        return back()->with('success', 'Prospect added.');
    }

    public function importCsv(Request $request, ProspectingCampaign $campaign)
    {
        $request->validate(['csv' => 'required|file|mimes:csv,txt']);

        $path = $request->file('csv')->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        $map = array_flip(array_map(fn ($h) => strtolower(trim($h)), $header ?: []));
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = [
                'name' => $row[$map['name'] ?? 99] ?? null,
                'email' => $row[$map['email'] ?? 99] ?? null,
                'title' => $row[$map['title'] ?? 99] ?? null,
                'company' => $row[$map['company'] ?? 99] ?? null,
                'company_size' => $row[$map['company_size'] ?? 99] ?? null,
                'industry' => $row[$map['industry'] ?? 99] ?? null,
                'location' => $row[$map['location'] ?? 99] ?? null,
                'website' => $row[$map['website'] ?? 99] ?? null,
                'linkedin_url' => $row[$map['linkedin_url'] ?? 99] ?? null,
            ];

            if (empty(array_filter($data))) {
                continue;
            }

            $email = strtolower(trim((string) ($data['email'] ?? '')));
            if ($email && $campaign->prospects()->where('email', $email)->exists()) {
                continue;
            }

            $campaign->prospects()->create(array_merge($data, ['email' => $email ?: null, 'source' => 'csv', 'status' => 'new']));
            $count++;
        }
        fclose($handle);

        return back()->with('success', "Imported {$count} prospects from CSV.");
    }

    public function qualifyProspect(Prospect $prospect, ProspectQualifierService $qualifier)
    {
        $result = $qualifier->qualify($prospect);

        return back()->with('success', "Prospect scored {$result['score']}/10 ({$result['status']}).");
    }

    public function generateProspect(Prospect $prospect, OutreachService $outreach)
    {
        $outreach->generate($prospect);

        return back()->with('success', '2-pass AI email generated. Review and send it below.');
    }

    public function sendProspect(Prospect $prospect, OutreachService $outreach)
    {
        $result = $outreach->send($prospect, true);

        return empty($result['sent'])
            ? back()->with('error', $result['error'] ?? 'Could not send email.')
            : back()->with('success', 'Email sent to ' . $prospect->email);
    }

    public function markReplied(Request $request, Prospect $prospect, ReplyAlertService $alerts)
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
        ]);

        $alerts->recordReply($prospect, $prospect->email ?? 'manual', $validated['subject'] ?? 'Reply', $validated['body'], ['source' => 'manual']);

        return back()->with('success', 'Reply recorded and alerts dispatched.');
    }

    public function destroyProspect(Prospect $prospect)
    {
        $prospect->delete();

        return back()->with('success', 'Prospect removed.');
    }



    public function suppressionList(SuppressionService $suppression)
    {
        return Inertia::render('Platform/Prospecting/SuppressionList', [
            'entries' => $suppression->all(),
        ]);
    }

    public function suppressProspect(Prospect $prospect, SuppressionService $suppression)
    {
        $suppression->suppress($prospect->email, 'dnc', $prospect->organization_id, $prospect->campaign_id, 'manual');
        $prospect->update(['status' => 'unsubscribed', 'suppressed_at' => now(), 'suppression_reason' => 'dnc']);
        $prospect->events()->create(['organization_id' => $prospect->organization_id, 'type' => 'suppressed', 'payload' => ['reason' => 'dnc']]);

        return back()->with('success', 'Prospect added to the do-not-contact list.');
    }

    public function unsuppress(Request $request, SuppressionService $suppression)
    {
        $suppression->remove($request->input('email'));

        return back()->with('success', 'Removed from the do-not-contact list.');
    }

    public function validateProspect(Prospect $prospect, ContactValidator $validator)
    {
        $validator->validateAndStore($prospect);

        return back()->with('success', "Validation complete: {$prospect->validation_status}.");
    }

    protected function validateCampaign(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'offer' => 'nullable|string',
            'tone' => 'nullable|string|in:professional,friendly,persuasive,concise',
            'sender_name' => 'nullable|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'daily_limit' => 'nullable|integer|min:1|max:500',
            'auto_outreach' => 'boolean',
            'icp_industry' => 'nullable|string',
            'icp_company_size' => 'nullable|string',
            'icp_geography' => 'nullable|string',
            'icp_job_titles' => 'nullable|string',
            'icp_keywords' => 'nullable|string',
            'icp_exclusions' => 'nullable|string',
            'icp_budget' => 'nullable|string',
            'icp_pain_points' => 'nullable|string',
            'postal_address' => 'nullable|string',
            'from_domain' => 'nullable|string|max:255',
            'compliance_regions' => 'nullable|string',
            'blocked_sources' => 'nullable|string',
            'blocked_regions' => 'nullable|string',
            'allow_ai_generated' => 'boolean',
            'max_per_hour' => 'nullable|integer|min:1|max:1000',
            'require_approval_ai_contacts' => 'boolean',
        ]);
    }

    protected function buildIcp(Request $request): array
    {
        return [
            'industry' => $this->split($request->input('icp_industry')),
            'company_size' => $request->input('icp_company_size'),
            'geography' => $this->split($request->input('icp_geography')),
            'job_titles' => $this->split($request->input('icp_job_titles')),
            'keywords' => $this->split($request->input('icp_keywords')),
            'exclusions' => $this->split($request->input('icp_exclusions')),
            'budget' => $request->input('icp_budget'),
            'pain_points' => $request->input('icp_pain_points'),
        ];
    }

    protected function split(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $value))));
    }
}
