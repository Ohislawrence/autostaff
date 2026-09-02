<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'first_name', 'last_name', 'email', 'phone',
        'company', 'source', 'channel', 'external_id',
        'metadata', 'notes', 'tags', 'lead_score', 'lead_stage',
        'last_contacted_at', 'ai_summary',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'ai_summary' => 'array',
        'last_contacted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->uuid = (string) Str::uuid();
        });
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}