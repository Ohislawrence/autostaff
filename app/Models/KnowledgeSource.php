<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KnowledgeSource extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'knowledge_base_id', 'type', 'title', 'content',
        'file_path', 'source_url', 'status', 'progress', 'error_message', 'chunk_count',
        'version', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'progress' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (KnowledgeSource $source) {
            $source->uuid = (string) Str::uuid();
        });
    }

    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function document()
    {
        return $this->hasOne(KnowledgeDocument::class);
    }
}