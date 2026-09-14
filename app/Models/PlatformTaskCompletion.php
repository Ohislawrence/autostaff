<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformTaskCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_task_id', 'completed_on',
    ];

    protected $casts = [
        'completed_on' => 'date',
    ];

    public function task()
    {
        return $this->belongsTo(PlatformTask::class, 'platform_task_id');
    }
}
