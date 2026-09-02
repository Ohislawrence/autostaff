<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PromoCode extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'code', 'type', 'value', 'min_order_amount',
        'starts_at', 'ends_at', 'usage_limit', 'usage_count', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromoCode $code) {
            $code->uuid = (string) Str::uuid();
        });
    }

    public function isUsable(float $orderAmount): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && now()->gt($this->ends_at)) {
            return false;
        }
        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }
        if ($orderAmount < (float) $this->min_order_amount) {
            return false;
        }
        return true;
    }

    public function discountFor(float $orderAmount): float
    {
        if ($this->type === 'fixed') {
            return min((float) $this->value, $orderAmount);
        }
        return round($orderAmount * ((float) $this->value / 100), 2);
    }
}