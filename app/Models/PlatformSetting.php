<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = [
        'vat_rate', 'additional_taxes', 'manual_payment_email',
        'manual_payment_instructions', 'company_name', 'company_address',
        'company_tax_id', 'invoice_prefix',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:2',
        'additional_taxes' => 'array',
    ];

    public static function instance(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'vat_rate' => 7.50,
                'invoice_prefix' => 'INV',
            ]
        );
    }
}
