<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PluginDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'plugin_version_id',
        'ip',
        'user_agent',
    ];

    public function pluginVersion()
    {
        return $this->belongsTo(PluginVersion::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
