<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProspectingCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'ai_employee_id', 'name', 'description', 'icp', 'offer', 'tone',
        'buyer_persona_id', 'buyer_persona_snapshot',
        'sender_name', 'sender_email', 'daily_limit', 'auto_outreach',
        'status', 'last_run_at', 'last_outreach_at',
        'postal_address', 'from_domain', 'compliance_regions', 'sourcing_rules',
        'send_window', 'max_per_hour', 'require_approval_ai_contacts',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'buyer_persona_id' => 'integer',
        'icp' => 'array',
        'buyer_persona_snapshot' => 'array',
        'auto_outreach' => 'boolean',
        'daily_limit' => 'integer',
        'max_per_hour' => 'integer',
        'require_approval_ai_contacts' => 'boolean',
        'compliance_regions' => 'array',
        'sourcing_rules' => 'array',
        'send_window' => 'array',
        'last_run_at' => 'datetime',
        'last_outreach_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProspectingCampaign $campaign) {
            $campaign->uuid = (string) Str::uuid();
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function aiEmployee()
    {
        return $this->belongsTo(AiEmployee::class, 'ai_employee_id');
    }

    public function buyerPersona()
    {
        return $this->belongsTo(BuyerPersona::class, 'buyer_persona_id');
    }

    /**
     * Human-readable persona summary for injecting into AI prompts.
     * Falls back to the live relationship when no snapshot is present.
     */
    public function personaPromptSummary(): ?string
    {
        if (! empty($this->buyer_persona_snapshot)) {
            $persona = new BuyerPersona($this->buyer_persona_snapshot);
            $persona->exists = false;

            return $persona->toPromptSummary();
        }

        return $this->buyerPersona?->toPromptSummary();
    }

    public function prospects()
    {
        return $this->hasMany(Prospect::class, 'campaign_id');
    }

    public function outreachMessages()
    {
        return $this->hasMany(OutreachMessage::class, 'campaign_id');
    }
}
