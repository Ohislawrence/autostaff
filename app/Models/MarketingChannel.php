<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarketingChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'type', 'goal', 'budget', 'status',
        'start_at', 'end_at', 'notes',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MarketingChannel $channel) {
            $channel->uuid = (string) Str::uuid();
        });
    }

    public function metrics()
    {
        return $this->hasMany(MarketingMetric::class, 'channel_id');
    }
}
