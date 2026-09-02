<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscription extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'plan_id', 'status',
        'trial_ends_at', 'starts_at', 'ends_at', 'cancelled_at',
        'provider', 'provider_subscription_id', 'provider_data',
    ];

    protected $casts = [
        'provider_data' => 'array',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription) {
            $subscription->uuid = (string) Str::uuid();
        });
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
