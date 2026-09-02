<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ToolExecution extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'tool_id', 'ai_employee_id', 'conversation_id', 'message_id',
        'input_parameters', 'output_result', 'status', 'error_message',
        'execution_time_ms', 'estimated_cost', 'requires_confirmation', 'was_confirmed',
        'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'input_parameters' => 'array',
        'output_result' => 'array',
        'execution_time_ms' => 'integer',
        'estimated_cost' => 'decimal:6',
        'requires_confirmation' => 'boolean',
        'was_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ToolExecution $e) {
            $e->uuid = (string) Str::uuid();
        });
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function aiEmployee()
    {
        return $this->belongsTo(AiEmployee::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}