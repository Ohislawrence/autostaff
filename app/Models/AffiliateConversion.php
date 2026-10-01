<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AffiliateConversion extends Model
{
    protected $fillable = [
        'uuid', 'organization_id', 'network', 'click_id', 'sub_id', 'event',
        'plan_slug', 'sale_amount', 'payout_amount', 'currency', 'status',
        'response_code', 'response_body', 'attempts', 'sent_at',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'response_code' => 'integer',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AffiliateConversion $conversion) {
            if (! $conversion->uuid) {
                $conversion->uuid = (string) Str::uuid();
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
