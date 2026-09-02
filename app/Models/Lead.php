<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'customer_id', 'ai_employee_id', 'conversation_id',
        'stage', 'source', 'score', 'score_breakdown', 'estimated_value',
        'product_interest', 'notes', 'metadata', 'converted_at', 'converted_by',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'metadata' => 'array',
        'estimated_value' => 'decimal:2',
        'converted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Lead $lead) {
            $lead->uuid = (string) Str::uuid();
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function aiEmployee()
    {
        return $this->belongsTo(AiEmployee::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class);
    }
}