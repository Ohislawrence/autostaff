<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProspectEvent extends Model
{
    protected $fillable = [
        'uuid', 'prospect_id', 'organization_id', 'type', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProspectEvent $event) {
            $event->uuid = (string) Str::uuid();
        });
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }
}
