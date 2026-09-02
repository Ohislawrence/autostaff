<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KnowledgeChunk extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'knowledge_document_id', 'content', 'embedding',
        'chunk_index', 'token_count', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeChunk $chunk) {
            $chunk->uuid = (string) Str::uuid();
        });
    }

    public function document()
    {
        return $this->belongsTo(KnowledgeDocument::class, 'knowledge_document_id');
    }

    public function knowledgeBase()
    {
        return $this->hasOneThrough(
            \App\Models\KnowledgeBase::class,
            \App\Models\KnowledgeDocument::class,
            'id',
            'id',
            'knowledge_document_id',
            'knowledge_source_id'
        )->join('knowledge_sources', 'knowledge_sources.id', '=', 'knowledge_documents.knowledge_source_id');
    }
}