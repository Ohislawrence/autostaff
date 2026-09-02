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
        'uuid', 'organization_id', 'name', 'description', 'icp', 'offer', 'tone',
        'sender_name', 'sender_email', 'daily_limit', 'auto_outreach',
        'status', 'last_run_at', 'last_outreach_at',
        'postal_address', 'from_domain', 'compliance_regions', 'sourcing_rules',
        'send_window', 'max_per_hour', 'require_approval_ai_contacts',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'icp' => 'array',
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

    public function prospects()
    {
        return $this->hasMany(Prospect::class, 'campaign_id');
    }

    public function outreachMessages()
    {
        return $this->hasMany(OutreachMessage::class, 'campaign_id');
    }
}
