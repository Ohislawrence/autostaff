<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'conversation_id', 'sender_id', 'customer_id', 'ai_employee_id',
        'type', 'content', 'metadata', 'channel_message_id', 'channel',
        'is_read', 'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Message $message) {
            $message->uuid = (string) Str::uuid();
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function aiEmployee()
    {
        return $this->belongsTo(AiEmployee::class);
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }
}