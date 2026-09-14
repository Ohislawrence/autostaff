<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlatformTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'title', 'description', 'category', 'priority',
        'status', 'recurrence', 'due_date', 'completed_at', 'streak_count',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'streak_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PlatformTask $task) {
            $task->uuid = (string) Str::uuid();
        });
    }

    public function completions()
    {
        return $this->hasMany(PlatformTaskCompletion::class, 'platform_task_id');
    }
}
