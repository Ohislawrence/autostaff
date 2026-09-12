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
        'configure_profile' => ['label' => 'Company Profile', 'next' => 'sales_goal'],
        'sales_goal' => ['label' => 'What You Sell', 'next' => 'icp'],
        'icp' => ['label' => 'Your Ideal Customer', 'next' => 'launch'],
        'launch' => ['label' => 'Hire Your Sales Rep', 'next' => 'results'],
        'results' => ['label' => 'Your First Prospects', 'next' => null],
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
        $campaign = null;
        $prospects = collect();

        if ($currentStep === 'results') {
            $campaign = \App\Models\ProspectingCampaign::where('organization_id', $organization->id)
                ->whereNotNull('ai_employee_id')
                ->latest()
                ->first();
            $aiEmployee = $campaign?->aiEmployee;
            $prospects = $campaign?->prospects()->orderByDesc('score')->limit(10)->get() ?? collect();
        }

        $policies = $organization->policies ?? [];

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
            'offering' => $policies['offering'] ?? ($organization->description ?? ''),
            'pricePoint' => $policies['price_point'] ?? '',
            'icp' => $policies['icp'] ?? null,
            'campaign' => $campaign ? [
                'id' => $campaign->id,
                'name' => $campaign->name,
            ] : null,
            'prospects' => $prospects->values(),
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

        // Start on the Free plan (no payment required).
        try {
            app(\App\Services\Billing\SubscriptionService::class)->subscribeFree($organization);
        } catch (\Throwable $e) {
            // Never block onboarding if the Free plan isn't available.
        }

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
            'onboarding_step' => 'sales_goal',
        ]));

        return redirect()->route('onboarding.show');
    }

    /**
     * Step 3: What do you sell, and at what price point?
     */
    public function storeSalesGoal(Request $request)
    {
        $organization = current_org();

        $validated = $request->validate([
            'offering' => 'required|string|max:255',
            'price_point' => 'nullable|string|max:100',
        ]);

        $organization->update([
            'policies' => array_merge($organization->policies ?? [], [
                'offering' => $validated['offering'],
                'price_point' => $validated['price_point'] ?? null,
            ]),
            'onboarding_step' => 'icp',
        ]);

        return redirect()->route('onboarding.show');
    }

    /**
     * Step 4: Who do you want as customers (ideal customer profile)?
     */
    public function storeIcp(Request $request)
    {
        $organization = current_org();

        $validated = $request->validate([
            'industry' => 'nullable|string|max:255',
            'company_size' => 'nullable|string|max:100',
            'geography' => 'nullable|string|max:255',
            'keywords' => 'nullable|string|max:255',
            'budget' => 'nullable|string|max:100',
            'pain_points' => 'nullable|string|max:255',
        ]);

        $policies = $organization->policies ?? [];
        $policies['icp'] = [
            'industry' => $this->splitList($validated['industry'] ?? ''),
            'company_size' => $validated['company_size'] ?? null,
            'geography' => $this->splitList($validated['geography'] ?? ''),
            'keywords' => $this->splitList($validated['keywords'] ?? ''),
            'budget' => $validated['budget'] ?? null,
            'pain_points' => $validated['pain_points'] ?? null,
        ];

        $organization->update([
            'policies' => $policies,
            'onboarding_step' => 'launch',
        ]);

        return redirect()->route('onboarding.show');
    }

    /**
     * Step 5: Create the AI Sales Employee, build the ICP campaign, and
     * run the first Hunt → Qualify pass synchronously.
     */
    public function launchCampaign(Request $request)
    {
        $organization = current_org();

        $validated = $request->validate([
            'employee_name' => 'nullable|string|max:255',
        ]);

        $policies = $organization->policies ?? [];

        app(\App\Services\Onboarding\FirstCustomerService::class)->launch($organization, [
            'employee_name' => $validated['employee_name'] ?? null,
            'offering' => $policies['offering'] ?? null,
            'price_point' => $policies['price_point'] ?? null,
            'icp' => $policies['icp'] ?? [],
        ]);

        $organization->update([
            'onboarding_step' => 'results',
        ]);

        return redirect()->route('onboarding.show');
    }

    /**
     * Final step: mark onboarding complete and go to the dashboard.
     */
    public function finish()
    {
        current_org()?->update([
            'onboarding_completed' => true,
        ]);

        return redirect()->route('dashboard');
    }

    /**
     * Split a comma/newline-separated string into a cleaned list.
     */
    protected function splitList(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $value))));
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