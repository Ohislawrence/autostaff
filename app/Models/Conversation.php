<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'ai_employee_id', 'customer_id', 'assigned_user_id',
        'channel', 'channel_conversation_id', 'subject', 'status', 'priority',
        'ai_summary', 'metadata', 'last_message_at', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            $conversation->uuid = (string) Str::uuid();
        });
    }

    public function aiEmployee()
    {
        return $this->belongsTo(AiEmployee::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function toolExecutions()
    {
        return $this->hasMany(ToolExecution::class);
    }

    public function aiRuns()
    {
        return $this->hasMany(AiRun::class);
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function tags()
    {
        return $this->hasMany(ConversationTag::class);
    }
}