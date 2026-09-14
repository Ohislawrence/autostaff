<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'name', 'description', 'icon', 'points',
        'condition_key', 'target', 'sort_order',
    ];

    protected $casts = [
        'points' => 'integer',
        'target' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function unlocks()
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id');
    }
}
