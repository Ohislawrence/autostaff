<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Prospect extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'campaign_id', 'organization_id', 'name', 'email', 'title', 'company',
        'company_size', 'industry', 'location', 'website', 'linkedin_url',
        'source', 'source_url', 'provenance', 'discovered_at', 'score', 'score_breakdown', 'qualification_notes',
        'research_notes', 'research_sources', 'researched_at',
        'data_subject_type', 'legal_basis', 'consent_status',
        'validation_status', 'validation_details', 'validated_at',
        'status', 'email_subject', 'email_body', 'pass',
        'followup_count', 'last_followup_at',
        'contacted_at', 'replied_at', 'last_reply_at', 'reply_summary',
        'intent', 'meeting_booked_at', 'meeting_link',
        'unsubscribe_token', 'suppressed_at', 'suppression_reason', 'metadata',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'score' => 'integer',
        'pass' => 'integer',
        'followup_count' => 'integer',
        'score_breakdown' => 'array',
        'metadata' => 'array',
        'provenance' => 'array',
        'research_sources' => 'array',
        'validation_details' => 'array',
        'contacted_at' => 'datetime',
        'replied_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'validated_at' => 'datetime',
        'discovered_at' => 'datetime',
        'researched_at' => 'datetime',
        'last_followup_at' => 'datetime',
        'meeting_booked_at' => 'datetime',
        'suppressed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Prospect $prospect) {
            $prospect->uuid = (string) Str::uuid();
            $prospect->unsubscribe_token = $prospect->unsubscribe_token ?: (string) Str::random(40);
            $prospect->discovered_at = $prospect->discovered_at ?: now();
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaign()
    {
        return $this->belongsTo(ProspectingCampaign::class, 'campaign_id');
    }

    public function messages()
    {
        return $this->hasMany(OutreachMessage::class);
    }

    public function events()
    {
        return $this->hasMany(ProspectEvent::class);
    }
}
