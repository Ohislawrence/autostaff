<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid',
        'organization_id',
        'customer_id',
        'ai_employee_id',
        'conversation_id',
        'title',
        'description',
        'service',
        'start_time',
        'end_time',
        'timezone',
        'status',
        'location',
        'meeting_link',
        'notes',
        'metadata',
        'reminder_sent',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'metadata' => 'array',
        'reminder_sent' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            $appointment->uuid = (string) Str::uuid();
            if (! $appointment->status) {
                $appointment->status = 'scheduled';
            }
            if (! $appointment->timezone) {
                $appointment->timezone = 'UTC';
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function aiEmployee()
    {
        return $this->belongsTo(AiEmployee::class, 'ai_employee_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNoShow($query)
    {
        return $query->where('status', 'no_show');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['scheduled', 'confirmed'])
            ->where('start_time', '>=', now());
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function scopeNeedsReminder($query)
    {
        return $query->where('reminder_sent', false)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('start_time', '>', now())
            ->where('start_time', '<=', now()->addHour());
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isUpcoming(): bool
    {
        return in_array($this->status, ['scheduled', 'confirmed'])
            && $this->start_time->isFuture();
    }

    public function isPast(): bool
    {
        return $this->start_time->isPast();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['scheduled', 'confirmed'])
            && $this->start_time->isFuture();
    }

    public function durationInMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }
}