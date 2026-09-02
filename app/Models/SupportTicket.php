<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory, TenantAware;

    protected $table = 'customer_support_tickets';

    protected $fillable = [
        'uuid', 'organization_id', 'customer_id', 'conversation_id',
        'subject', 'description', 'status', 'priority', 'assigned_to', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            $ticket->uuid = (string) Str::uuid();
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}