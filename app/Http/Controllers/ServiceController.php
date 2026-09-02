<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function store(Request $request)
    {
        $organization = current_org();
        abort_if(! $organization, 403, 'No active organization.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'price' => 'nullable|numeric|min:0',
        ]);

        $organization->services()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? 30,
            'price' => $validated['price'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Service added.');
    }

    public function update(Request $request, Service $service)
    {
        if ($service->organization_id !== current_org_id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service->update($validated);

        return back()->with('success', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        if ($service->organization_id !== current_org_id()) {
            abort(403);
        }

        $service->delete();

        return back()->with('success', 'Service removed.');
    }
}
