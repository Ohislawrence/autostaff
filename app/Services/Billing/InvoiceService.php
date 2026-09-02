<?php

namespace App\Services\Billing;

use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;

class InvoiceService
{
    public function create(
        Organization $organization,
        ?Subscription $subscription,
        string $description,
        float $subtotal,
        float $taxAmount,
        float $total,
        string $currency,
        float $vatRate,
        array $taxes,
        ?string $paymentReference = null,
    ): SubscriptionInvoice {
        return SubscriptionInvoice::create([
            'organization_id' => $organization->id,
            'subscription_id' => $subscription?->id,
            'invoice_number' => $this->nextNumber(),
            'description' => $description,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'currency' => $currency,
            'vat_rate' => $vatRate,
            'taxes' => $taxes,
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
            'due_at' => now()->addMonth(),
        ]);
    }

    public function nextNumber(): string
    {
        $settings = PlatformSetting::instance();
        $prefix = $settings->invoice_prefix ?: 'INV';
        $count = SubscriptionInvoice::max('id') + 1;

        return strtoupper($prefix) . '-' . now()->format('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
