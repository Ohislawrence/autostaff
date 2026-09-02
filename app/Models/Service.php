<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'organization_id',
        'uuid',
        'name',
        'description',
        'duration_minutes',
        'price',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Service $service) {
            $service->uuid = (string) Str::uuid();
        });
    }
}