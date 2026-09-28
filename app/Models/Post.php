<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'excerpt',
        'body',
        'featured_image',
        'meta_title',
        'meta_description',
        'og_image',
        'author_id',
        'status',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            $post->uuid = $post->uuid ?: (string) Str::uuid();
            $post->slug = $post->slug ?: Str::slug($post->title);
            $post->status = $post->status ?: 'draft';
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image
            ? Storage::disk('public')->url($this->featured_image)
            : null;
    }

    public function getOgImageUrlAttribute(): string
    {
        $path = $this->og_image ?: $this->featured_image;

        return $path ? Storage::disk('public')->url($path) : url('/images/og-image2.png');
    }

    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function getSeoDescriptionAttribute(): string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        if ($this->excerpt) {
            return $this->excerpt;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->body))), 160);
    }
}
