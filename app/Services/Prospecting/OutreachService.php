<?php

namespace App\Services\Prospecting;

use App\Ai\Providers\AiProviderInterface;
use App\Ai\Providers\AiResponse;
use App\Models\Prospect;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Guardrails\CostGuardService;
use App\Support\AiJson;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OutreachService
{
    public function __construct(
        protected AiProviderInterface $ai,
        protected ProspectingSettingsService $settings,
        protected ComplianceGate $gate,
        protected ContactValidator $validator,
        protected AiUsageRecorder $recorder,
        protected CostGuardService $costGuard,
    ) {}

    /**
     * Run the 2-pass AI copywriting flow and store the final email on the prospect.
     */
    public function generate(Prospect $prospect): array
    {
        $campaign = $prospect->campaign;

        $draft = $this->generatePass($prospect, $campaign, 1, null);
        $prospect->messages()->create([
            'campaign_id' => $campaign->id,
            'direction' => 'outbound',
            'pass' => 1,
            'subject' => $draft['subject'],
            'body' => $draft['body'],
            'status' => 'draft',
        ]);

        $final = $this->generatePass($prospect, $campaign, 2, $draft);
        $prospect->messages()->create([
            'campaign_id' => $campaign->id,
            'direction' => 'outbound',
            'pass' => 2,
            'subject' => $final['subject'],
            'body' => $final['body'],
            'status' => 'draft',
        ]);

        $prospect->update([
            'email_subject' => $final['subject'],
            'email_body' => $final['body'],
            'pass' => 2,
        ]);

        return $final;
    }

    /**
     * Generate (if needed) then actually send the email.
     */
    public function send(Prospect $prospect, bool $force = false): array
    {
        if (! $prospect->email) {
            return ['sent' => false, 'error' => 'Prospect has no email address.'];
        }

        if (empty($prospect->unsubscribe_token)) {
            $prospect->update(['unsubscribe_token' => Str::random(40)]);
        }

        if ($prospect->validation_status === 'pending') {
            $this->validator->validateAndStore($prospect);
        }

        $gate = $this->gate->check($prospect);

        if ($gate['status'] === 'blocked') {
            $this->markCompliance($prospect, $gate);

            return [
                'sent' => false,
                'error' => 'Blocked by compliance: ' . implode(', ', $gate['blockers']),
                'compliance' => $gate,
            ];
        }

        if ($gate['status'] === 'needs_review' && ! $force) {
            $this->markCompliance($prospect, $gate);

            return [
                'sent' => false,
                'needs_review' => true,
                'error' => 'Compliance review required before sending.',
                'compliance' => $gate,
            ];
        }

        if ($prospect->pass < 2 || empty($prospect->email_body)) {
            $this->generate($prospect);
            $prospect->refresh();
        }

        $subject = $prospect->email_subject ?: 'Quick question';
        $body = $prospect->email_body;
        $senderName = $prospect->campaign->sender_name ?: $this->settings->senderName();
        $senderEmail = $prospect->campaign->sender_email ?: $this->settings->senderEmail();
        $postal = $prospect->campaign->postal_address ?: $this->settings->postalAddress();
        $unsubscribeUrl = url('/prospecting/unsubscribe/' . $prospect->unsubscribe_token);

        $body = $this->appendFooter($body, $postal, $unsubscribeUrl);

        try {
            Mail::raw($body, function ($message) use ($prospect, $subject, $senderName, $senderEmail, $unsubscribeUrl) {
                $message->to($prospect->email, $prospect->name ?? null)
                    ->subject($subject)
                    ->from($senderEmail, $senderName)
                    ->replyTo($senderEmail, $senderName);
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
        } catch (\Throwable $e) {
            Log::error('Prospecting email send failed', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);

            return ['sent' => false, 'error' => $e->getMessage()];
        }

        $prospect->messages()
            ->where('direction', 'outbound')
            ->where('pass', 2)
            ->latest()
            ->first()?->update([
                'status' => 'sent',
                'sent_at' => now(),
                'compliance_status' => 'approved',
                'compliance_checks' => $gate['checks'],
                'message_headers' => [
                    'list_unsubscribe' => $unsubscribeUrl,
                    'list_unsubscribe_post' => 'List-Unsubscribe=One-Click',
                ],
            ]);

        $prospect->update([
            'status' => 'contacted',
            'contacted_at' => $prospect->contacted_at ?? now(),
        ]);
        $prospect->campaign->update(['last_outreach_at' => now()]);
        $prospect->events()->create([
            'organization_id' => $prospect->organization_id,
            'type' => 'sent',
            'payload' => ['subject' => $subject],
        ]);

        return ['sent' => true, 'subject' => $subject, 'compliance' => $gate];
    }

    /**
     * Send a follow-up email to a prospect who hasn't replied yet.
     */
    public function sendFollowup(Prospect $prospect): array
    {
        if (! $prospect->email) {
            return ['sent' => false, 'error' => 'Prospect has no email address.'];
        }

        if (empty($prospect->unsubscribe_token)) {
            $prospect->update(['unsubscribe_token' => Str::random(40)]);
        }

        $gate = $this->gate->check($prospect);

        if ($gate['status'] === 'blocked') {
            $this->markCompliance($prospect, $gate);

            return ['sent' => false, 'error' => 'Blocked by compliance: ' . implode(', ', $gate['blockers']), 'compliance' => $gate];
        }

        if ($gate['status'] === 'needs_review') {
            $this->markCompliance($prospect, $gate);

            return ['sent' => false, 'needs_review' => true, 'error' => 'Compliance review required before sending.', 'compliance' => $gate];
        }

        $content = $this->generateFollowupContent($prospect);

        $senderName = $prospect->campaign->sender_name ?: $this->settings->senderName();
        $senderEmail = $prospect->campaign->sender_email ?: $this->settings->senderEmail();
        $postal = $prospect->campaign->postal_address ?: $this->settings->postalAddress();
        $unsubscribeUrl = url('/prospecting/unsubscribe/' . $prospect->unsubscribe_token);

        $body = $this->appendFooter($content['body'], $postal, $unsubscribeUrl);

        try {
            Mail::raw($body, function ($message) use ($prospect, $content, $senderName, $senderEmail, $unsubscribeUrl) {
                $message->to($prospect->email, $prospect->name ?? null)
                    ->subject($content['subject'])
                    ->from($senderEmail, $senderName)
                    ->replyTo($senderEmail, $senderName);
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
        } catch (\Throwable $e) {
            Log::error('Prospecting follow-up send failed', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);

            return ['sent' => false, 'error' => $e->getMessage()];
        }

        $prospect->increment('followup_count');
        $prospect->update(['last_followup_at' => now()]);
        $prospect->campaign->update(['last_outreach_at' => now()]);
        $prospect->events()->create([
            'organization_id' => $prospect->organization_id,
            'type' => 'followup_sent',
            'payload' => ['subject' => $content['subject'], 'n' => $prospect->followup_count],
        ]);

        return ['sent' => true, 'subject' => $content['subject'], 'compliance' => $gate];
    }

    /**
     * Generate a follow-up email body + subject (a lighter, bump-style note).
     */
    protected function generateFollowupContent(Prospect $prospect): array
    {
        $campaign = $prospect->campaign;
        $context = $this->prospectContext($prospect);
        $offer = $campaign?->offer ?: 'N/A';
        $icp = $this->prettyJson($campaign?->icp ?? []);
        $tone = $campaign?->tone ?? 'professional';

        $user = <<<PROMPT
Write a short, polite follow-up to a cold email you already sent to this prospect.

Campaign ICP:
{$icp}

Offer / value proposition:
{$offer}

Tone: {$tone}

Prospect:
{$context}

Return ONLY valid JSON: {"subject": "...", "body": "..."}
Rules: under 80 words, one clear call-to-action, reference the earlier email briefly, do not be pushy.
PROMPT;

        $options = ['temperature' => 0.6, 'max_tokens' => 800];
        if ($model = $this->settings->deepseekModel()) {
            $options['model'] = $model;
        }

        if (! $this->costGuard->checkBudget($prospect->organization_id)) {
            return [
                'subject' => 'Re: ' . ($prospect->email_subject ?: 'Quick question'),
                'body' => $this->fallbackBody($prospect, $campaign),
            ];
        }

        try {
            $response = $this->ai->chat([
                ['role' => 'system', 'content' => 'You are a B2B sales rep writing brief, polite follow-up emails.'],
                ['role' => 'user', 'content' => $user],
            ], $options);

            $this->recordRun($prospect, $response, 'You are a B2B sales rep writing brief, polite follow-up emails.', $user);

            $data = AiJson::parse($response->content);
        } catch (\Throwable $e) {
            $data = null;
        }

        if (! is_array($data) || empty($data['body'])) {
            $data = [
                'subject' => 'Re: ' . ($prospect->email_subject ?: 'Quick question'),
                'body' => $this->fallbackBody($prospect, $campaign),
            ];
        }

        $subject = trim((string) ($data['subject'] ?? 'Quick follow-up'));
        $body = trim((string) ($data['body'] ?? ''));

        $prospect->messages()->create([
            'campaign_id' => $campaign->id,
            'direction' => 'outbound',
            'pass' => 3,
            'subject' => $subject,
            'body' => $body,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return ['subject' => $subject, 'body' => $body];
    }

    protected function recordRun(Prospect $prospect, AiResponse $response, string $system, string $user): void
    {
        $this->recorder->record($prospect->organization_id, $response, [
            'system_prompt' => $system,
            'user_prompt' => $user,
        ]);
    }

    protected function markCompliance(Prospect $prospect, array $gate): void
    {
        $prospect->messages()
            ->where('direction', 'outbound')
            ->where('pass', 2)
            ->latest()
            ->first()?->update([
                'compliance_status' => $gate['status'],
                'compliance_checks' => $gate['checks'],
            ]);
    }

    protected function appendFooter(string $body, ?string $postal, string $unsubscribeUrl): string
    {
        $footer = '';
        if ($postal) {
            $footer .= "\n\n" . $postal;
        }
        $footer .= "\n\nUnsubscribe: {$unsubscribeUrl}";

        return $body . $footer;
    }

    protected function generatePass(Prospect $prospect, $campaign, int $pass, ?array $draft): array
    {
        $context = $this->prospectContext($prospect);

        if ($pass === 1) {
            $system = 'You are an elite B2B outbound copywriter. You write short, human, hyper-personalized cold emails that never sound like spam.';
            $offer = $campaign->offer ?: 'N/A';
            $persona = $campaign->personaPromptSummary();
            $personaBlock = $persona ? "Buyer persona:\n{$persona}\n" : '';

            $user = <<<PROMPT
Write a personalized cold email to this prospect.

Campaign ICP:
{$this->prettyJson($campaign->icp ?? [])}

{$personaBlock}Offer / value proposition:
{$offer}

Tone: {$campaign->tone}

Prospect:
{$context}

Return ONLY valid JSON: {"subject": "...", "body": "..."}
Rules: 1 clear CTA, under 120 words, reference something specific about the prospect's company or role, and address the persona's pains/goals.
PROMPT;
            $temperature = 0.8;
        } else {
            $system = 'You are a ruthless editor. You refine cold emails for higher reply rates while keeping them natural and specific.';
            $user = <<<PROMPT
Refine this first draft into a final email.

First draft:
Subject: {$draft['subject']}
Body: {$draft['body']}

Tone: {$campaign->tone}

Return ONLY valid JSON: {"subject": "...", "body": "..."}
Rules: sharpen the personalization hook, fix grammar/flow, keep one clear CTA, under 110 words, no spam words.
PROMPT;
            $temperature = 0.5;
        }

        $options = [
            'temperature' => $temperature,
            'max_tokens' => 1000,
        ];
        if ($model = $this->settings->deepseekModel()) {
            $options['model'] = $model;
        }

        if (! $this->costGuard->checkBudget($prospect->organization_id)) {
            return [
                'subject' => 'Quick question for ' . ($prospect->company ?: $prospect->name ?: 'you'),
                'body' => $this->fallbackBody($prospect, $campaign),
            ];
        }

        $response = $this->ai->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], $options);

        $this->recordRun($prospect, $response, $system, $user);

        $data = AiJson::parse($response->content);

        if (! is_array($data) || empty($data['body'])) {
            return [
                'subject' => $data['subject'] ?? ('Quick question for ' . ($prospect->company ?: $prospect->name ?: 'you')),
                'body' => $data['body'] ?? $this->fallbackBody($prospect, $campaign),
            ];
        }

        return [
            'subject' => trim((string) $data['subject']) ?: ('Quick question for ' . ($prospect->company ?: $prospect->name ?: 'you')),
            'body' => trim((string) $data['body']),
        ];
    }

    protected function prospectContext(Prospect $prospect): string
    {
        $lines = [
            'Name: ' . ($prospect->name ?: 'N/A'),
            'Title: ' . ($prospect->title ?: 'N/A'),
            'Company: ' . ($prospect->company ?: 'N/A'),
            'Company size: ' . ($prospect->company_size ?: 'N/A'),
            'Industry: ' . ($prospect->industry ?: 'N/A'),
            'Location: ' . ($prospect->location ?: 'N/A'),
            'Website: ' . ($prospect->website ?: 'N/A'),
            'LinkedIn: ' . ($prospect->linkedin_url ?: 'N/A'),
        ];

        return implode("\n", $lines);
    }

    protected function fallbackBody(Prospect $prospect, $campaign): string
    {
        $name = $prospect->name ? explode(' ', trim($prospect->name))[0] : 'there';
        $company = $prospect->company ?: 'your company';

        return "Hi {$name},\n\nI came across {$company} and wanted to reach out.\n\n"
            . ($campaign->offer ? trim($campaign->offer) . "\n\n" : '')
            . "Would you be open to a quick chat this week?\n\nThanks!";
    }

    protected function prettyJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
