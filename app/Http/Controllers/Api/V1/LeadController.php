<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = Lead::with('customer')
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($leads);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'stage' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'estimated_value' => ['nullable', 'numeric'],
            'product_interest' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $customerId = $validated['customer_id'] ?? null;

        if ($customerId) {
            Customer::findOrFail($customerId); // tenant-scoped existence check
        } elseif (! empty($validated['email'])) {
            $customer = Customer::where('email', $validated['email'])->first();

            if (! $customer) {
                $customer = Customer::create([
                    'first_name' => $validated['first_name'] ?? '',
                    'last_name' => $validated['last_name'] ?? null,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'source' => $validated['source'] ?? null,
                ]);
            }

            $customerId = $customer->id;
        }

        $lead = Lead::create([
            'customer_id' => $customerId,
            'stage' => $validated['stage'] ?? 'new',
            'source' => $validated['source'] ?? null,
            'estimated_value' => $validated['estimated_value'] ?? null,
            'product_interest' => $validated['product_interest'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json($lead->load('customer'), 201);
    }
}
