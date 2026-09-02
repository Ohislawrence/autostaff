<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KnowledgeBase extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'name', 'description', 'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeBase $kb) {
            $kb->uuid = (string) Str::uuid();
        });
    }

    public function sources()
    {
        return $this->hasMany(KnowledgeSource::class);
    }

    public function documents()
    {
        return $this->hasManyThrough(KnowledgeDocument::class, KnowledgeSource::class);
    }
}