<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PluginVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'plugin_id',
        'version',
        'changelog',
        'file_path',
        'file_name',
        'file_size',
        'sha256_checksum',
        'status',
        'is_current',
        'published_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_current' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function plugin()
    {
        return $this->belongsTo(Plugin::class);
    }
}
