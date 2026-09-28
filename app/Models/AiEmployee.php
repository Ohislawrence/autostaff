<?php

namespace App\Models;

use App\Tenant\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AiEmployee extends Model
{
    use HasFactory, SoftDeletes, TenantAware;

    protected $fillable = [
        'uuid', 'organization_id', 'name', 'role', 'department', 'description', 'avatar',
        'system_instructions', 'personality', 'tone', 'language',
        'business_knowledge_ids', 'enabled_tools', 'allowed_channels',
        'working_hours', 'escalation_rules', 'response_settings', 'widget_settings',
        'ai_model', 'temperature', 'max_tool_calls', 'max_context_messages',
        'is_active', 'is_template', 'template_type',
        'conversations_count', 'leads_generated', 'escalation_rate',
    ];

    protected $casts = [
        'business_knowledge_ids' => 'array',
        'enabled_tools' => 'array',
        'allowed_channels' => 'array',
        'working_hours' => 'array',
        'escalation_rules' => 'array',
        'response_settings' => 'array',
        'widget_settings' => 'array',
        'temperature' => 'float',
        'is_active' => 'boolean',
        'is_template' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiEmployee $employee) {
            $employee->uuid = (string) Str::uuid();
        });
    }

    public function tools()
    {
        return $this->belongsToMany(Tool::class, 'ai_employee_tool')
            ->withPivot('is_allowed', 'requires_confirmation', 'max_per_conversation')
            ->withTimestamps();
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function aiRuns()
    {
        return $this->hasMany(AiRun::class);
    }

    public function toolExecutions()
    {
        return $this->hasMany(ToolExecution::class);
    }

    public function prospectingCampaigns()
    {
        return $this->hasMany(ProspectingCampaign::class, 'ai_employee_id');
    }

    public function prospects()
    {
        return $this->hasManyThrough(Prospect::class, ProspectingCampaign::class, 'ai_employee_id', 'campaign_id', 'id', 'id');
    }
}