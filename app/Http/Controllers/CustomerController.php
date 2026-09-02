<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Crm\LeadScoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(protected LeadScoringService $scoringService) {}

    public function index(Request $request)
    {
        $organization = current_org();
        $customers = $organization->customers()
            ->withCount(['conversations', 'orders', 'leads'])
            ->when($request->search, fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', "%{$request->search}%")->orWhere('last_name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%")->orWhere('phone', 'like', "%{$request->search}%")->orWhere('company', 'like', "%{$request->search}%")))
            ->when($request->stage, fn ($q) => $q->where('lead_stage', $request->stage))
            ->latest('last_contacted_at')->paginate(20)->withQueryString();
        $stats = ['total' => $organization->customers()->count(), 'with_leads' => $organization->customers()->whereHas('leads')->count(), 'qualified' => $organization->customers()->where('lead_stage', 'qualified')->count()];
        return Inertia::render('Customers/Index', ['customers' => $customers, 'stats' => $stats, 'filters' => $request->only(['search', 'stage'])]);
    }

    public function show(Customer $customer)
    {
        $orgId = current_org_id();
        if ($customer->organization_id !== $orgId) abort(403);
        $customer->load(['conversations' => fn ($q) => $q->latest()->take(5), 'leads', 'orders' => fn ($q) => $q->latest()->take(5)]);
        return Inertia::render('Customers/Show', ['customer' => $customer, 'leadScore' => $this->scoringService->calculate($customer)]);
    }

    public function update(Request $request, Customer $customer)
    {
        $orgId = current_org_id();
        if ($customer->organization_id !== $orgId) abort(403);
        $validated = $request->validate(['first_name' => 'required|string|max:255', 'last_name' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:50', 'company' => 'nullable|string|max:255', 'notes' => 'nullable|string', 'lead_stage' => 'nullable|string', 'tags' => 'nullable|array']);
        if ($request->has('tags')) $validated['tags'] = $request->tags;
        $customer->update($validated);
        $this->scoringService->updateScore($customer);
        return back()->with('success', 'Customer updated.');
    }
}