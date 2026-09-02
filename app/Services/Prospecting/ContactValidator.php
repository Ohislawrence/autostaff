<?php

namespace App\Services\Prospecting;

use App\Models\Prospect;

class ContactValidator
{
    protected array $disposableDomains = [
        'mailinator.com', 'guerrillamail.com', '10minutemail.com', 'tempmail.com',
        'throwawaymail.com', 'yopmail.com', 'getnada.com', 'trashmail.com',
        'sharklasers.com', 'spam4.me', 'maildrop.cc', 'mintemail.com',
        'temp-mail.org', 'dispostable.com', 'fakeinbox.com', 'mailnesia.com',
    ];

    protected array $rolePrefixes = [
        'info', 'noreply', 'no-reply', 'support', 'sales', 'hello', 'contact',
        'admin', 'abuse', 'billing', 'marketing', 'team', 'office', 'enquiries',
        'enquiry', 'help', 'careers', 'hr', 'jobs', 'postmaster', 'webmaster',
    ];

    protected array $freeDomains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com',
        'icloud.com', 'protonmail.com', 'gmx.com', 'zoho.com',
    ];

    /**
     * Free validation: syntax + MX + disposable + role-account detection.
     */
    public function validate(?string $email): array
    {
        $email = strtolower(trim((string) $email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->result('invalid', ['syntax' => false]);
        }

        [, $domain] = explode('@', $email, 2);

        if (in_array($domain, $this->disposableDomains, true)) {
            return $this->result('disposable', ['disposable' => true]);
        }

        $prefix = explode('.', explode('@', $email, 2)[0])[0];
        if (in_array($prefix, $this->rolePrefixes, true)) {
            return $this->result('role', ['role_account' => true]);
        }

        $mx = $this->hasMx($domain);
        if (! $mx) {
            return $this->result('risky', ['mx' => false, 'syntax' => true]);
        }

        return $this->result('valid', ['syntax' => true, 'mx' => true]);
    }

    public function validateAndStore(Prospect $prospect): void
    {
        $result = $this->validate($prospect->email);

        $prospect->update([
            'validation_status' => $result['status'],
            'validation_details' => $result['details'],
            'validated_at' => now(),
        ]);

        $prospect->events()->create([
            'organization_id' => $prospect->organization_id,
            'type' => 'validated',
            'payload' => $result,
        ]);
    }

    public function isFreeDomain(?string $email): bool
    {
        $domain = strtolower(substr((string) $email, (int) strpos((string) $email, '@') + 1));

        return in_array($domain, $this->freeDomains, true);
    }

    protected function hasMx(string $domain): bool
    {
        if (! function_exists('checkdnsrr')) {
            return true; // environment can't check — don't block.
        }

        return (bool) @checkdnsrr($domain, 'MX');
    }

    protected function result(string $status, array $details): array
    {
        return ['status' => $status, 'details' => $details];
    }
}
