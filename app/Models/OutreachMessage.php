<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OutreachMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'prospect_id', 'campaign_id', 'organization_id', 'direction', 'pass',
        'subject', 'body', 'status', 'compliance_status', 'compliance_checks',
        'message_headers', 'provider_message_id',
        'sent_at', 'delivered_at', 'bounced_at', 'complained_at', 'metadata',
    ];

    protected $casts = [
        'pass' => 'integer',
        'organization_id' => 'integer',
        'metadata' => 'array',
        'compliance_checks' => 'array',
        'message_headers' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OutreachMessage $message) {
            $message->uuid = (string) Str::uuid();
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function campaign()
    {
        return $this->belongsTo(ProspectingCampaign::class, 'campaign_id');
    }
}
