<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id', 'organization_id', 'reason',
        'status', 'actions_count', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}