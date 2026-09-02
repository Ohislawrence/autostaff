<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgeGap extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'category', 'source', 'question',
        'frequency', 'status', 'suggested_answer', 'last_seen_at',
    ];

    protected $casts = [
        'frequency' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeGap $gap) {
            $gap->uuid = (string) Str::uuid();
        });
    }
}