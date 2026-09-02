<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'price', 'usd_price', 'currency',
        'billing_period', 'max_ai_employees', 'max_messages_per_month',
        'max_tool_calls_per_month', 'max_knowledge_sources',
        'max_storage_bytes', 'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'usd_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Plan $plan) {
            $plan->uuid = (string) Str::uuid();
        });
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if this plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        
        // Check for exact match or partial match in feature list
        foreach ($features as $f) {
            if (str_contains(strtolower($f), strtolower($feature))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if this plan allows API access.
     */
    public function allowsApiAccess(): bool
    {
        return in_array($this->slug, ['professional', 'enterprise']);
    }

    /**
     * Check if this plan allows custom tools.
     */
    public function allowsCustomTools(): bool
    {
        return in_array($this->slug, ['professional', 'enterprise']);
    }

    /**
     * Check if this plan allows all channels.
     */
    public function allowsAllChannels(): bool
    {
        return in_array($this->slug, ['business', 'professional', 'enterprise']);
    }

    /**
     * Check if this plan allows appointments.
     */
    public function allowsAppointments(): bool
    {
        return in_array($this->slug, ['business', 'professional', 'enterprise']);
    }

    /**
     * Check if this plan allows commerce features.
     */
    public function allowsCommerce(): bool
    {
        return in_array($this->slug, ['business', 'professional', 'enterprise']);
    }

    /**
     * Check if this plan includes CRM scoring.
     */
    public function allowsCrmScoring(): bool
    {
        return in_array($this->slug, ['business', 'professional', 'enterprise']);
    }

    /**
     * Get display price.
     */
    public function getDisplayPrice(): string
    {
        if ($this->price == 0) {
            return 'Custom';
        }
        
        return '$' . number_format($this->price, 0);
    }

    /**
     * Resolve the charge amount + currency for a given billing currency.
     * NGN (Nigeria) is the base; other regions fall back to USD.
     */
    public function priceFor(string $currency): array
    {
        if (strtoupper($currency) === 'NGN') {
            return ['amount' => (float) $this->price, 'currency' => 'NGN'];
        }

        $rate = max((float) config('services.currency.ngn_to_usd', 1500), 1);
        $usd = $this->usd_price !== null
            ? (float) $this->usd_price
            : round((float) $this->price / $rate, 2);

        return ['amount' => $usd, 'currency' => 'USD'];
    }

    /**
     * Check if this is a popular tier.
     */
    public function isPopular(): bool
    {
        return $this->slug === 'business';
    }
}
