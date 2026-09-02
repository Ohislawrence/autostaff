<?php

namespace App\Services\Prospecting;

use App\Models\ProspectingSettings;

class ProspectingSettingsService
{
    public function get(): ProspectingSettings
    {
        return ProspectingSettings::instance();
    }

    public function telegramToken(): ?string
    {
        return $this->get()->telegram_bot_token ?: config('services.telegram.bot_token');
    }

    public function telegramChatId(): ?string
    {
        return $this->get()->telegram_chat_id ?: config('services.telegram.chat_id');
    }

    public function alertEmail(): ?string
    {
        return $this->get()->alert_email
            ?: config('services.prospecting.alert_email')
            ?: config('mail.from.address');
    }

    public function senderName(): string
    {
        return $this->get()->sender_name ?: config('mail.from.name', config('app.name', 'Outreach'));
    }

    public function senderEmail(): string
    {
        return $this->get()->sender_email ?: config('mail.from.address');
    }

    public function signature(): ?string
    {
        return $this->get()->signature;
    }

    public function deepseekModel(): ?string
    {
        return $this->get()->deepseek_model ?: null;
    }

    public function postalAddress(): ?string
    {
        return $this->get()->postal_address;
    }

    public function supportEmail(): ?string
    {
        return $this->get()->support_email ?: $this->alertEmail();
    }

    public function defaultLegalBasis(): string
    {
        return $this->get()->default_legal_basis ?: 'legitimate_interest';
    }

    public function maxEmailsPerHour(): int
    {
        return (int) ($this->get()->max_emails_per_hour ?: 50);
    }

    public function requireApprovalAiContacts(): bool
    {
        return (bool) $this->get()->require_approval_ai_contacts;
    }

    /**
     * Update settings. Empty secret fields are treated as "keep existing".
     */
    public function save(array $data): ProspectingSettings
    {
        $data = array_filter($data, fn ($value) => $value !== null);

        if (isset($data['telegram_bot_token']) && trim((string) $data['telegram_bot_token']) === '') {
            unset($data['telegram_bot_token']);
        }
        if (isset($data['search_api_key']) && trim((string) $data['search_api_key']) === '') {
            unset($data['search_api_key']);
        }

        $settings = $this->get();
        $settings->update($data);

        return $settings;
    }
}
