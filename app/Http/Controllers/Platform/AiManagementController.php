<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AiEmployee;
use App\Models\Plan;
use App\Models\Tool;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiManagementController extends Controller
{
    // Providers
    public function providers()
    {
        $providers = [
            [
                'name' => 'DeepSeek',
                'key' => 'deepseek',
                'status' => !empty(env('DEEPSEEK_API_KEY')) ? 'active' : 'inactive',
                'models' => ['deepseek-chat', 'deepseek-reasoner'],
                'is_default' => true,
                'config' => ['base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com')],
            ],
            [
                'name' => 'OpenAI',
                'key' => 'openai',
                'status' => !empty(env('OPENAI_API_KEY')) ? 'active' : 'inactive',
                'models' => ['gpt-4o', 'gpt-4o-mini'],
                'is_default' => false,
                'config' => [],
            ],
            [
                'name' => 'Anthropic',
                'key' => 'anthropic',
                'status' => 'inactive',
                'models' => ['claude-3-opus', 'claude-3-sonnet'],
                'is_default' => false,
                'config' => [],
            ],
        ];

        return Inertia::render('Platform/AiProviders', ['providers' => $providers]);
    }

    // Models
    public function models()
    {
        $models = [
            ['name' => 'deepseek-chat', 'provider' => 'DeepSeek', 'status' => 'active', 'is_default' => true, 'plan_availability' => ['starter', 'business', 'professional', 'enterprise'], 'token_limit' => 128000, 'context_limit' => 128000],
            ['name' => 'deepseek-reasoner', 'provider' => 'DeepSeek', 'status' => 'active', 'is_default' => false, 'plan_availability' => ['business', 'professional', 'enterprise'], 'token_limit' => 64000, 'context_limit' => 64000],
            ['name' => 'gpt-4o', 'provider' => 'OpenAI', 'status' => 'inactive', 'is_default' => false, 'plan_availability' => ['professional', 'enterprise'], 'token_limit' => 128000, 'context_limit' => 128000],
            ['name' => 'gpt-4o-mini', 'provider' => 'OpenAI', 'status' => 'inactive', 'is_default' => false, 'plan_availability' => ['business', 'professional', 'enterprise'], 'token_limit' => 128000, 'context_limit' => 128000],
        ];

        $plans = Plan::orderBy('sort_order')->get(['id', 'name', 'slug']);

        return Inertia::render('Platform/AiModels', ['models' => $models, 'plans' => $plans]);
    }

    // Templates
    public function templates()
    {
        // Get templates without tenant scope
        $templates = AiEmployee::withoutGlobalScope(\App\Tenant\TenantScope::class)
            ->where('is_template', true)
            ->get();

        return Inertia::render('Platform/AiTemplates', [
            'templates' => $templates,
            'availableTools' => Tool::where('is_active', true)->select('id', 'identifier', 'name', 'description', 'category')->get(),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'description' => 'nullable|string',
            'system_instructions' => 'nullable|string',
            'personality' => 'nullable|string',
            'tone' => 'nullable|string',
            'template_type' => 'nullable|string',
            'enabled_tools' => 'nullable|array',
        ]);

        // Templates don't belong to any organization
        $template = AiEmployee::withoutGlobalScope(\App\Tenant\TenantScope::class)->create(array_merge($validated, [
            'organization_id' => null,
            'is_template' => true,
            'is_active' => false,
        ]));

        return back()->with('success', "Template '{$template->name}' created.");
    }

    public function updateTemplate(Request $request, AiEmployee $aiEmployee)
    {
        if (!$aiEmployee->is_template) {
            return back()->with('error', 'Only templates can be updated here.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'description' => 'nullable|string',
            'system_instructions' => 'nullable|string',
            'personality' => 'nullable|string',
            'tone' => 'nullable|string',
            'template_type' => 'nullable|string',
            'enabled_tools' => 'nullable|array',
        ]);

        $aiEmployee->update($validated);

        return back()->with('success', "Template '{$aiEmployee->name}' updated.");
    }

    public function destroyTemplate(AiEmployee $aiEmployee)
    {
        if (!$aiEmployee->is_template) {
            return back()->with('error', 'Only templates can be deleted here.');
        }

        $name = $aiEmployee->name;
        $aiEmployee->delete();

        return back()->with('success', "Template '{$name}' deleted.");
    }

    public function toggleTemplate(AiEmployee $aiEmployee)
    {
        if (!$aiEmployee->is_template) {
            return back()->with('error', 'Only templates can be toggled.');
        }
        
        $aiEmployee->update(['is_active' => ! $aiEmployee->is_active]);
        return back()->with('success', $aiEmployee->is_active ? 'Template published.' : 'Template unpublished.');
    }

    // Tools
    public function tools()
    {
        $tools = Tool::where('is_custom', false)->orderBy('category')->get();

        return Inertia::render('Platform/AiTools', ['tools' => $tools]);
    }

    public function updateTool(Request $request, Tool $tool)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'requires_confirmation' => 'boolean',
            'is_active' => 'boolean',
            'risk_level' => 'nullable|string|in:read,write,sensitive,restricted',
        ]);

        $tool->update($validated);
        return back()->with('success', "Tool '{$tool->name}' updated.");
    }

    // Feature Flags
    public function featureFlags()
    {
        $flags = \DB::table('platform_feature_flags')->get();
        $plans = Plan::orderBy('sort_order')->get(['id', 'name', 'slug']);

        // Default flags if none exist
        if ($flags->isEmpty()) {
            $defaults = [
                ['key' => 'whatsapp', 'name' => 'WhatsApp Integration', 'is_enabled' => true],
                ['key' => 'advanced_analytics', 'name' => 'Advanced Analytics', 'is_enabled' => true],
                ['key' => 'custom_ai_tools', 'name' => 'Custom AI Tools', 'is_enabled' => false],
                ['key' => 'workflow_automation', 'name' => 'Workflow Automation', 'is_enabled' => true],
                ['key' => 'ai_evaluations', 'name' => 'AI Evaluations', 'is_enabled' => false],
                ['key' => 'custom_api', 'name' => 'Custom API', 'is_enabled' => false],
                ['key' => 'sales_development_rep', 'name' => 'Sales Development Rep (Prospecting)', 'is_enabled' => true],
            ];
            foreach ($defaults as $d) {
                \DB::table('platform_feature_flags')->insert(array_merge($d, ['created_at' => now(), 'updated_at' => now()]));
            }
            $flags = \DB::table('platform_feature_flags')->get();
        }

        // Ensure the SDR flag always exists so it can be toggled (default on).
        \DB::table('platform_feature_flags')->insertOrIgnore([
            'name' => 'Sales Development Rep (Prospecting)',
            'key' => 'sales_development_rep',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $flags = \DB::table('platform_feature_flags')->get();

        return Inertia::render('Platform/FeatureFlags', ['flags' => $flags, 'plans' => $plans]);
    }

    public function updateFeatureFlag(Request $request, $id)
    {
        \DB::table('platform_feature_flags')->where('id', $id)->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'plan_availability' => $request->plan_availability ? json_encode($request->plan_availability) : null,
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Feature flag updated.');
    }
}