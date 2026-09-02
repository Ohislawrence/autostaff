<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Automation extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'name', 'description', 'trigger_type', 'trigger_config',
        'conditions', 'actions', 'is_active', 'max_executions_per_day',
        'execution_count_today', 'last_executed_at',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Automation $automation) {
            $automation->uuid = (string) Str::uuid();
        });
    }

    public function runs()
    {
        return $this->hasMany(AutomationRun::class);
    }
}