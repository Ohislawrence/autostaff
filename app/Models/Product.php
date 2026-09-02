<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'name', 'description', 'sku', 'price', 'sale_price',
        'currency', 'category', 'tags', 'attributes', 'image', 'images',
        'is_active', 'is_digital', 'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'attributes' => 'array',
        'images' => 'array',
        'metadata' => 'array',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_digital' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->uuid = (string) Str::uuid();
        });
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}