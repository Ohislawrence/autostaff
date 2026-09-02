<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'uuid', 'organization_id', 'invoice_id', 'provider', 'reference',
        'amount', 'currency', 'status', 'error', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SubscriptionPayment $payment) {
            $payment->uuid = (string) Str::uuid();
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'invoice_id');
    }
}
