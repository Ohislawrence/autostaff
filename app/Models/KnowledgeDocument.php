<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgeDocument extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'knowledge_source_id', 'content', 'metadata',
        'chunk_count', 'status',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeDocument $doc) {
            $doc->uuid = (string) Str::uuid();
        });
    }

    public function source()
    {
        return $this->belongsTo(KnowledgeSource::class, 'knowledge_source_id');
    }

    public function chunks()
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}