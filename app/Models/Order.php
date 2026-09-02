<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'customer_id', 'conversation_id', 'ai_employee_id',
        'order_number', 'status', 'subtotal', 'tax', 'shipping', 'discount', 'total',
        'currency', 'shipping_address', 'billing_address',
        'payment_status', 'payment_method', 'notes', 'metadata',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'metadata' => 'array',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->uuid = (string) Str::uuid();
            if (! $order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(10));
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function aiEmployee()
    {
        return $this->belongsTo(AiEmployee::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}