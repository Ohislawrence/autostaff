<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GeneratedReport extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'period', 'period_start', 'period_end',
        'metrics', 'summary_text',
    ];

    protected $casts = [
        'metrics' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (GeneratedReport $report) {
            $report->uuid = (string) Str::uuid();
        });
    }
}