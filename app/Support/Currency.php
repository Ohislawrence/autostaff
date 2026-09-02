<?php

namespace App\Support;

/**
 * Centralized currency handling for the multi-tenant platform.
 *
 * Each Organization stores its own ISO-4217 currency code. This class maps
 * codes to display symbols and provides consistent formatting so that
 * tenant-facing tools, reports, and portals all reflect the tenant's currency.
 */
class Currency
{
    /**
     * Supported currency codes and their display symbols.
     *
     * @var array<string, string>
     */
    protected const SYMBOLS = [
        'USD' => '$',
        'NGN' => '₦',
        'GHS' => 'GH₵',
        'KES' => 'KSh',
        'ZAR' => 'R',
        'EUR' => '€',
        'GBP' => '£',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'INR' => '₹',
        'JPY' => '¥',
    ];

    /**
     * Currencies that do not conventionally use decimal places.
     */
    protected const ZERO_DECIMAL = ['JPY'];

    /**
     * Get the display symbol for a currency code.
     */
    public static function symbol(?string $code): string
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            $code = 'NGN';
        }

        return self::SYMBOLS[$code] ?? ($code . ' ');
    }

    /**
     * Get the list of supported currency codes.
     *
     * @return string[]
     */
    public static function supported(): array
    {
        return array_keys(self::SYMBOLS);
    }

    /**
     * Format an amount with the given currency symbol and code.
     */
    public static function format(float|int|string $amount, ?string $code = null): string
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            $code = 'NGN';
        }

        $decimals = in_array($code, self::ZERO_DECIMAL, true) ? 0 : 2;
        $number = number_format((float) $amount, $decimals, '.', ',');

        return self::symbol($code) . $number;
    }

    /**
     * Normalize a currency code, falling back to USD when unknown/empty.
     */
    public static function normalize(?string $code): string
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '' || ! isset(self::SYMBOLS[$code])) {
            return 'NGN';
        }

        return $code;
    }
}