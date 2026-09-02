<?php

namespace App\Services\Prospecting;

use App\Models\OutreachMessage;
use App\Models\Prospect;

class ComplianceGate
{
    public function __construct(
        protected SuppressionService $suppression,
        protected ProspectingSettingsService $settings,
    ) {}

    /**
     * The single choke point every outbound send must pass through.
     */
    public function check(Prospect $prospect): array
    {
        $campaign = $prospect->campaign;
        $checks = [];
        $blockers = [];
        $needsReview = false;

        // 1. Suppression / do-not-contact.
        $suppressed = $this->suppression->isSuppressed($prospect->email);
        $checks[] = $this->row('suppressed', ! $suppressed, $suppressed ? 'Email is on the do-not-contact list.' : 'Not suppressed.');
        if ($suppressed) {
            $blockers[] = 'suppressed';
        }

        // 2. Contact validation.
        $vs = $prospect->validation_status;
        $checks[] = $this->row('validation', ! in_array($vs, ['invalid', 'disposable'], true), "Validation status: {$vs}.");
        if (in_array($vs, ['invalid', 'disposable'], true)) {
            $blockers[] = 'validation';
        } elseif (in_array($vs, ['risky', 'role', 'pending'], true)) {
            $needsReview = true;
        }

        // 3. Sourcing / AI-approval (configurable, default off).
        $requireApproval = (bool) ($campaign->require_approval_ai_contacts ?? $this->settings->requireApprovalAiContacts());
        if ($prospect->source === 'ai_generated' && $requireApproval) {
            $checks[] = $this->row('sourcing_approval', false, 'AI-generated contact requires human approval.');
            $needsReview = true;
        } else {
            $checks[] = $this->row('sourcing_approval', true, 'Sourcing rules satisfied.');
        }

        // 4. Data-subject basis (UK PECR/GDPR for individuals & sole traders).
        $regions = $campaign->compliance_regions ?: ['us', 'uk'];
        $isUk = in_array('uk', $regions, true);
        $requiresBasis = $isUk && $prospect->data_subject_type !== 'corporate';
        $hasBasis = in_array($prospect->legal_basis, ['consent', 'legitimate_interest'], true);
        $checks[] = $this->row('data_subject', ! ($requiresBasis && ! $hasBasis), 'Data-subject lawful basis satisfied.');
        if ($requiresBasis && ! $hasBasis) {
            $needsReview = true;
        }

        // 5. Identity (accurate sender + physical postal address).
        $postal = $campaign->postal_address ?: $this->settings->postalAddress();
        $senderEmail = $campaign->sender_email ?: $this->settings->senderEmail();
        $identityOk = ! empty($postal) && ! empty($senderEmail);
        $checks[] = $this->row('identity', $identityOk, $identityOk ? 'Sender + physical postal address present.' : 'Missing sender email or physical postal address.');
        if (! $identityOk) {
            $blockers[] = 'identity';
        }

        // 5b. Sending limits (per-hour throttle).
        $maxPerHour = (int) ($campaign->max_per_hour ?: $this->settings->maxEmailsPerHour());
        $sentLastHour = OutreachMessage::where('campaign_id', $prospect->campaign_id)
            ->where('direction', 'outbound')
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', now()->subHour())
            ->count();
        $checks[] = $this->row('rate_limit', $sentLastHour < $maxPerHour, "Hourly sends: {$sentLastHour}/{$maxPerHour}.");
        if ($sentLastHour >= $maxPerHour) {
            $blockers[] = 'rate_limit';
        }

        // 6. Opt-out present.
        $checks[] = $this->row('opt_out', ! empty($prospect->unsubscribe_token), empty($prospect->unsubscribe_token) ? 'Missing unsubscribe token.' : 'Unsubscribe link present.');

        // 7. Non-deceptive subject.
        $subject = trim((string) $prospect->email_subject);
        $truthful = $subject !== '' && ! preg_match('/\b(free money|guaranteed|act now|winner|click here)\b/i', $subject);
        $checks[] = $this->row('subject', $truthful, 'Subject line check.');

        if (! empty($blockers)) {
            $status = 'blocked';
            $approved = false;
        } elseif ($needsReview) {
            $status = 'needs_review';
            $approved = false;
        } else {
            $status = 'approved';
            $approved = true;
        }

        return [
            'approved' => $approved,
            'status' => $status,
            'checks' => $checks,
            'blockers' => $blockers,
        ];
    }

    protected function row(string $rule, bool $passed, string $detail): array
    {
        return ['rule' => $rule, 'passed' => $passed, 'detail' => $detail];
    }
}
