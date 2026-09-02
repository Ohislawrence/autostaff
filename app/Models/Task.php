<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Task extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'title', 'description',
        'status', 'priority', 'assigned_to', 'customer_id',
        'conversation_id', 'lead_id', 'due_date', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'due_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            if (empty($task->uuid)) {
                $task->uuid = (string) Str::uuid();
            }
        });
    }
}
