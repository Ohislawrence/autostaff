<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('last_name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when($request->email, fn ($query) => $query->where('email', $request->email))
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
            'channel' => ['nullable', 'string', 'max:100'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }
}
