<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AutomationRun extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'organization_id',
        'automation_id',
        'execution_id',
        'status',
        'trigger_data',
        'condition_results',
        'action_results',
        'error_message',
        'workflow_depth',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'condition_results' => 'array',
        'action_results' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationRun $run) {
            if (empty($run->execution_id)) {
                $run->execution_id = 'auto_' . (string) Str::uuid();
            }
        });
    }

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }
}
