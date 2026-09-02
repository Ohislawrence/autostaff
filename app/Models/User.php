<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'uuid', 'name', 'email', 'password', 'avatar', 'phone',
        'timezone', 'is_active', 'last_login_at', 'last_active_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'last_active_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role', 'is_owner')
            ->withTimestamps();
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'assigned_user_id');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}