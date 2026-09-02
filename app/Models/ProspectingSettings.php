<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProspectingSettings extends Model
{
    protected $fillable = [
        'telegram_bot_token', 'telegram_chat_id', 'alert_email',
        'sender_name', 'sender_email', 'signature', 'deepseek_model',
        'search_provider', 'search_api_key', 'daily_hunt_limit',
        'daily_outreach_limit', 'qualification_threshold',
        'auto_hunt', 'auto_outreach', 'reply_webhook_secret',
        'postal_address', 'support_email', 'default_legal_basis',
        'max_emails_per_hour', 'require_approval_ai_contacts',
    ];

    protected $casts = [
        'daily_hunt_limit' => 'integer',
        'daily_outreach_limit' => 'integer',
        'qualification_threshold' => 'integer',
        'max_emails_per_hour' => 'integer',
        'auto_hunt' => 'boolean',
        'auto_outreach' => 'boolean',
        'require_approval_ai_contacts' => 'boolean',
    ];

    /**
     * The platform keeps exactly one settings row (id = 1).
     */
    public static function instance(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'search_provider' => 'none',
                'daily_hunt_limit' => 50,
                'daily_outreach_limit' => 50,
                'qualification_threshold' => 7,
                'auto_hunt' => false,
                'auto_outreach' => false,
                'default_legal_basis' => 'legitimate_interest',
                'max_emails_per_hour' => 50,
                'require_approval_ai_contacts' => false,
                'reply_webhook_secret' => Str::random(40),
            ]
        );
    }

    public function regenerateWebhookSecret(): void
    {
        $this->update(['reply_webhook_secret' => Str::random(40)]);
    }
}
