<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'organization_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'slot_duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];
}