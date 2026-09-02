<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'customer_id', 'conversation_id',
        'currency', 'status', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    protected static function booted(): void
    {
        static::creating(function (Cart $cart) {
            $cart->uuid = (string) Str::uuid();
        });
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function subtotal(): float
    {
        return (float) $this->items()->sum('total_price');
    }
}