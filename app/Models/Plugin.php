<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Plugin extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'short_description',
        'description',
        'icon',
        'author',
        'homepage_url',
        'category',
        'target_platform',
        'min_api_version',
        'max_api_version',
        'scopes',
        'is_published',
        'is_featured',
        'sort_order',
        'downloads_count',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'downloads_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Plugin $plugin) {
            if (! $plugin->uuid) {
                $plugin->uuid = (string) Str::uuid();
            }

            if (! $plugin->slug) {
                $plugin->slug = Str::slug($plugin->name);
            }
        });
    }

    public function versions()
    {
        return $this->hasMany(PluginVersion::class)->orderByDesc('created_at');
    }

    public function currentVersion()
    {
        return $this->hasOne(PluginVersion::class)->where('is_current', true);
    }

    public function installations()
    {
        return $this->hasMany(PluginInstallation::class);
    }
}
