<?php

namespace App\Http\Controllers;

use App\Models\AiEmployee;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    /** Steps in order. */
    protected const STEPS = [
        'create_org' => ['label' => 'Create Organization', 'next' => 'configure_profile'],
        'configure_profile' => ['label' => 'Company Profile', 'next' => 'create_ai_employee'],
        'create_ai_employee' => ['label' => 'Your First AI Employee', 'next' => 'complete'],
        'complete' => ['label' => 'All Set!', 'next' => null],
    ];

    /**
     * Show the onboarding wizard at the current step.
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        $organization = $this->resolveOrganization($user);

        // If no org at all, start from create_org
        if (! $organization) {
            return Inertia::render('Onboarding/Wizard', [
                'step' => 'create_org',
                'steps' => array_values(array_map(fn ($key, $s) => [
                    'key' => $key,
                    'label' => $s['label'],
                ], array_keys(self::STEPS), self::STEPS)),
                'organization' => null,
                'aiEmployee' => null,
            ]);
        }

        $currentStep = $organization->onboarding_step ?: 'create_org';

        // If completed, redirect to dashboard
        if ($organization->onboarding_completed || $currentStep === 'complete') {
            return redirect()->route('dashboard');
        }

        $aiEmployee = null;
        if (in_array($currentStep, ['complete'])) {
            $aiEmployee = $organization->aiEmployees()->latest()->first();
        }

        return Inertia::render('Onboarding/Wizard', [
            'step' => $currentStep,
            'steps' => array_values(array_map(fn ($key, $s) => [
                'key' => $key,
                'label' => $s['label'],
            ], array_keys(self::STEPS), self::STEPS)),
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'industry' => $organization->industry,
                'website' => $organization->website,
                'description' => $organization->description,
                'timezone' => $organization->timezone,
                'currency' => $organization->currency,
                'email' => $organization->email,
                'phone' => $organization->phone,
                'city' => $organization->city,
                'state' => $organization->state,
                'country' => $organization->country,
            ],
            'aiEmployee' => $aiEmployee ? [
                'id' => $aiEmployee->id,
                'name' => $aiEmployee->name,
                'personality' => $aiEmployee->personality,
                'language' => $aiEmployee->language,
            ] : null,
        ]);
    }

    /**
     * Step 1: Create the organization.
     */
    public function createOrganization(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();

        $organization = Organization::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'industry' => $request->industry,
            'onboarding_step' => 'configure_profile',
            'onboarding_completed' => false,
        ]);

        // Attach user as owner
        $organization->users()->attach($user->id, [
            'role' => 'Organization Owner',
            'is_owner' => true,
        ]);

        // Assign role
        $user->assignRole('Organization Owner');

        // Set as current org
        session(['current_organization_id' => $organization->id]);

        return redirect()->route('onboarding.show');
    }

    /**
     * Step 2: Save company profile details.
     */
    public function configureProfile(Request $request)
    {
        $organization = current_org();

        $validated = $request->validate([
            'website' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'timezone' => 'required|string|max:50',
            'currency' => 'required|string|size:3',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $organization->update(array_merge($validated, [
            'onboarding_step' => 'create_ai_employee',
        ]));

        return redirect()->route('onboarding.show');
    }

    /**
     * Step 3: Create the first AI employee.
     */
    public function createAiEmployee(Request $request)
    {
        $organization = current_org();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'personality' => 'nullable|string|max:1000',
            'language' => 'nullable|string|max:10',
        ]);

        AiEmployee::create(array_merge($validated, [
            'organization_id' => $organization->id,
            'uuid' => (string) Str::uuid(),
            'is_active' => true,
            'system_prompt' => $validated['personality'] ?? 'You are a helpful AI assistant for ' . $organization->name . '.',
        ]));

        $organization->update([
            'onboarding_step' => 'complete',
            'onboarding_completed' => true,
        ]);

        return redirect()->route('onboarding.show');
    }

    /**
     * Resolve the user's organization for onboarding context.
     */
    protected function resolveOrganization($user): ?Organization
    {
        if (session()->has('current_organization_id')) {
            return Organization::find(session('current_organization_id'));
        }

        return $user->organizations()->first();
    }
}