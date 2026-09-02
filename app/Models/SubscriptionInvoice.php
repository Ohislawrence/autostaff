<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionInvoice extends Model
{
    protected $fillable = [
        'uuid', 'organization_id', 'subscription_id', 'invoice_number',
        'description', 'subtotal', 'tax_amount', 'total', 'currency',
        'vat_rate', 'taxes', 'status', 'paid_at', 'payment_reference', 'due_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'taxes' => 'array',
        'paid_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SubscriptionInvoice $invoice) {
            $invoice->uuid = (string) Str::uuid();
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
