<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'currency' => 'NGN',
    ];

    protected $fillable = [
        'uuid', 'name', 'slug', 'logo', 'description', 'industry',
        'website', 'email', 'phone', 'address', 'city', 'state', 'country',
        'timezone', 'currency', 'business_hours', 'holidays', 'policies',
        'onboarding_step', 'onboarding_completed', 'is_active', 'trial_ends_at',
        'monthly_ai_budget_cents',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'holidays' => 'array',
        'policies' => 'array',
        'is_active' => 'boolean',
        'onboarding_completed' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            $organization->uuid = (string) Str::uuid();
            if (! $organization->slug) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'is_owner')
            ->withTimestamps();
    }

    public function aiEmployees()
    {
        return $this->hasMany(AiEmployee::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Resolve the organization's currently-active plan, falling back to Free.
     */
    public function activePlan(): ?Plan
    {
        $subscription = $this->subscriptions()
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($subscription && $subscription->plan) {
            return $subscription->plan;
        }

        return Plan::where('slug', 'free')->where('is_active', true)->first();
    }

    public function knowledgeBases()
    {
        return $this->hasMany(KnowledgeBase::class);
    }

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    public function integrationCredentials()
    {
        return $this->hasMany(IntegrationCredential::class);
    }

    public function automations()
    {
        return $this->hasMany(Automation::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function aiRuns()
    {
        return $this->hasMany(AiRun::class);
    }

    public function knowledgeSources()
    {
        return $this->hasMany(KnowledgeSource::class);
    }

    public function toolExecutions()
    {
        return $this->hasMany(ToolExecution::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function availabilities()
    {
        return $this->hasMany(Availability::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
