<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory, TenantAware;

    protected $table = 'inventory';

    protected $fillable = [
        'organization_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'low_stock_threshold',
        'warehouse_location',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'metadata' => 'array',
    ];
}