<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AiRun extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'ai_employee_id', 'conversation_id', 'message_id',
        'provider', 'model', 'input_tokens', 'output_tokens', 'latency_ms',
        'tools_called', 'knowledge_retrieved', 'system_prompt', 'user_prompt',
        'assistant_response', 'estimated_cost', 'status', 'error_message',
        'correlation_id', 'retry_count',
    ];

    protected $casts = [
        'tools_called' => 'array',
        'knowledge_retrieved' => 'array',
        'estimated_cost' => 'decimal:6',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'latency_ms' => 'integer',
        'retry_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiRun $run) {
            $run->uuid = (string) Str::uuid();
        });
    }
}