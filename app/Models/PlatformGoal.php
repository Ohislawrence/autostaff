<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlatformGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'title', 'metric_key', 'target', 'unit', 'period',
        'start_at', 'end_at', 'color', 'status',
    ];

    protected $casts = [
        'target' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PlatformGoal $goal) {
            $goal->uuid = (string) Str::uuid();
        });
    }
}
