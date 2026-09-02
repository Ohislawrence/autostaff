<?php

namespace App\Services\Billing;

use App\Models\PlatformSetting;

class TaxService
{
    /**
     * Compute VAT + any government-mandated taxes on a base amount.
     */
    public function calculate(float $amount, string $currency = 'NGN'): array
    {
        $settings = PlatformSetting::instance();
        $vatRate = (float) $settings->vat_rate;

        $taxes = [['name' => 'VAT', 'rate' => $vatRate, 'amount' => $this->round($amount * $vatRate / 100)]];

        foreach ((array) ($settings->additional_taxes ?? []) as $tax) {
            $rate = (float) ($tax['rate'] ?? 0);
            $name = $tax['name'] ?? 'Tax';
            if ($rate > 0) {
                $taxes[] = ['name' => $name, 'rate' => $rate, 'amount' => $this->round($amount * $rate / 100)];
            }
        }

        $taxAmount = $this->round(array_sum(array_column($taxes, 'amount')));

        return [
            'subtotal' => $this->round($amount),
            'vat_rate' => $vatRate,
            'taxes' => $taxes,
            'tax_amount' => $taxAmount,
            'total' => $this->round($amount + $taxAmount),
            'currency' => $currency,
        ];
    }

    protected function round(float $value): float
    {
        return round($value, 2);
    }
}
