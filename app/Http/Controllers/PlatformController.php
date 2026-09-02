<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\SubscriptionInvoice;
use App\Models\SupportSession;
use App\Models\User;
use App\Services\Platform\PlatformStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlatformController extends Controller
{
    public function __construct(protected PlatformStatsService $stats) {}

    // Dashboard
    public function dashboard()
    {
        return Inertia::render('Platform/Dashboard', [
            'stats' => $this->stats->getDashboardStats(),
            'health' => $this->stats->getSystemHealth(),
        ]);
    }

    // Tenants
    public function tenants(Request $request)
    {
        $tenants = Organization::with(['subscriptions.plan', 'users'])
            ->withCount(['aiEmployees', 'conversations', 'leads', 'users'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()->paginate(20);

        return Inertia::render('Platform/Tenants', [
            'tenants' => $tenants,
            'plans' => Plan::all(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function showTenant(Organization $organization)
    {
        $organization->load(['subscriptions.plan', 'users']);
        $organization->loadCount(['aiEmployees', 'customers', 'conversations', 'leads', 'orders']);

        $aiUsage = \App\Models\AiRun::where('organization_id', $organization->id)
            ->selectRaw('SUM(estimated_cost) as total_cost, SUM(input_tokens + output_tokens) as total_tokens, COUNT(*) as total_runs')
            ->where('created_at', '>=', now()->startOfMonth())
            ->first();

        return Inertia::render('Platform/TenantDetail', [
            'organization' => $organization,
            'plans' => Plan::orderBy('sort_order')->get(),
            'aiUsage' => [
                'cost' => round($aiUsage->total_cost ?? 0, 2),
                'tokens' => (int) ($aiUsage->total_tokens ?? 0),
                'runs' => (int) ($aiUsage->total_runs ?? 0),
            ],
        ]);
    }

    public function storeTenant(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:organizations,slug',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $org = Organization::create(array_merge($validated, [
            'onboarding_completed' => false,
            'is_active' => true,
        ]));

        // Optionally assign a plan
        if (! empty($validated['plan_id'])) {
            $org->subscriptions()->create([
                'plan_id' => $validated['plan_id'],
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
        }

        return redirect()->route('platform.tenants.show', $org)
            ->with('success', "Organization '{$org->name}' created successfully.");
    }

    public function addUser(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'user_email' => 'required|email|max:255',
            'role' => 'required|string|in:user,admin',
        ]);

        $user = User::where('email', $validated['user_email'])->first();
        if (! $user) {
            return back()->with('error', 'No user found with email: ' . $validated['user_email']);
        }

        if ($organization->users()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'User is already a member of this organization.');
        }

        $organization->users()->attach($user->id, ['role' => $validated['role']]);

        // If the user has no organization assigned, make this their default
        if (! $user->organizations()->where('organization_user.is_default', true)->exists()) {
            $user->organizations()->updateExistingPivot($organization->id, ['is_default' => true]);
        }

        return back()->with('success', "{$user->name} added as {$validated['role']}.");
    }

    public function updateBudget(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'monthly_ai_budget_cents' => 'nullable|numeric|min:0',
        ]);
        
        $dollars = $validated['monthly_ai_budget_cents'] ?? '';
        $cents = $dollars !== '' ? (int) round((float) $dollars * 100) : null;
        
        $organization->update(['monthly_ai_budget_cents' => $cents]);
        
        $label = $cents ? '$' . ($cents / 100) . '/mo' : 'Unlimited';
        return back()->with('success', "AI budget updated to {$label}");
    }

    public function toggleTenant(Organization $organization)
    {
        $organization->update(['is_active' => ! $organization->is_active]);
        return back()->with('success', $organization->is_active ? 'Organization activated.' : 'Organization suspended.');
    }

    public function updateTenantPlan(Request $request, Organization $organization)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);
        $organization->subscriptions()->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $organization->subscriptions()->create(['plan_id' => $request->plan_id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
        return back()->with('success', 'Plan updated.');
    }

    // Support Session
    public function startSupportSession(Request $request, Organization $organization)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $session = SupportSession::create([
            'admin_user_id' => Auth::id(),
            'organization_id' => $organization->id,
            'reason' => $request->reason,
            'status' => 'active',
            'started_at' => now(),
        ]);
        session(['support_session_id' => $session->id, 'current_organization_id' => $organization->id]);
        return redirect()->route('dashboard')->with('success', 'Support session started for ' . $organization->name . '.');
    }

    public function endSupportSession()
    {
        $sessionId = session('support_session_id');
        if ($sessionId) {
            SupportSession::where('id', $sessionId)->update(['status' => 'ended', 'ended_at' => now()]);
            session()->forget(['support_session_id', 'current_organization_id']);
        }
        return redirect()->route('platform.dashboard')->with('success', 'Support session ended.');
    }

    // Users
    public function users(Request $request)
    {
        $users = User::with('roles', 'organizations')
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%")))
            ->latest()->paginate(20);
        $roles = \Spatie\Permission\Models\Role::all();
        return Inertia::render('Platform/Users', ['users' => $users, 'roles' => $roles, 'filters' => $request->only(['search'])]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);
        $user->syncRoles([$request->role]);
        return back()->with('success', "{$user->name}'s role updated.");
    }

    // Plans
    public function plans()
    {
        return Inertia::render('Platform/Plans', ['plans' => Plan::orderBy('sort_order')->get()]);
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255', 'price' => 'required|numeric|min:0',
            'max_ai_employees' => 'required|integer|min:1', 'max_messages_per_month' => 'required|integer|min:1',
            'max_tool_calls_per_month' => 'required|integer|min:1', 'max_knowledge_sources' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'currency' => 'nullable|string|in:NGN,USD',
            'usd_price' => 'nullable|numeric|min:0',
        ]);
        $plan->update($validated);
        return back()->with('success', "Plan updated.");
    }

    // Settings
    public function settings()
    {
        return Inertia::render('Platform/Settings', [
            'settings' => ['app_name' => config('app.name'), 'app_url' => config('app.url'), 'deepseek_model' => config('services.deepseek.model', env('DEEPSEEK_MODEL', 'deepseek-chat'))],
        ]);
    }

    public function taxSettings()
    {
        return Inertia::render('Platform/TaxSettings', [
            'settings' => \App\Models\PlatformSetting::instance(),
        ]);
    }

    public function updateTaxSettings(Request $request)
    {
        $validated = $request->validate([
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'additional_taxes' => 'nullable|array',
            'additional_taxes.*.name' => 'required_with:additional_taxes|string|max:255',
            'additional_taxes.*.rate' => 'required_with:additional_taxes|numeric|min:0|max:100',
            'manual_payment_email' => 'nullable|email|max:255',
            'manual_payment_instructions' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_tax_id' => 'nullable|string|max:255',
            'invoice_prefix' => 'nullable|string|max:20',
        ]);

        $settings = \App\Models\PlatformSetting::instance();
        $settings->update([
            'vat_rate' => $validated['vat_rate'] ?? $settings->vat_rate,
            'additional_taxes' => $validated['additional_taxes'] ?? [],
            'manual_payment_email' => $validated['manual_payment_email'] ?? null,
            'manual_payment_instructions' => $validated['manual_payment_instructions'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'company_address' => $validated['company_address'] ?? null,
            'company_tax_id' => $validated['company_tax_id'] ?? null,
            'invoice_prefix' => $validated['invoice_prefix'] ?? 'INV',
        ]);

        return back()->with('success', 'Billing & tax settings saved.');
    }

    public function invoices(Request $request)
    {
        $invoices = SubscriptionInvoice::with('organization')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->organization, fn ($q) => $q->where('organization_id', $request->organization))
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('invoice_number', 'like', "%{$request->search}%")
                ->orWhereHas('organization', fn ($q) => $q->where('name', 'like', "%{$request->search}%"))))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Platform/Invoices', [
            'invoices' => $invoices,
            'organizations' => Organization::orderBy('name')->get(),
            'filters' => $request->only(['status', 'organization', 'search']),
        ]);
    }

    public function showInvoice(SubscriptionInvoice $invoice)
    {
        return view('invoices.subscription', [
            'invoice' => $invoice,
            'organization' => $invoice->organization,
            'settings' => \App\Models\PlatformSetting::instance(),
            'plan' => $invoice->subscription?->plan,
        ]);
    }

    public function markInvoicePaid(SubscriptionInvoice $invoice, \App\Services\Billing\SubscriptionService $subscriptions)
    {
        $subscriptions->markInvoicePaid($invoice);

        return back()->with('success', "Invoice {$invoice->invoice_number} marked as paid.");
    }
}