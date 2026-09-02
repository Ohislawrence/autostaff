<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shipment extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'order_id', 'carrier', 'tracking_number',
        'status', 'shipped_at', 'delivered_at', 'events', 'metadata',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'events' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            $shipment->uuid = (string) Str::uuid();
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}