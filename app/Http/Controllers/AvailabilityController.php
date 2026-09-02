<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function store(Request $request)
    {
        $organization = current_org();
        abort_if(! $organization, 403, 'No active organization.');

        $validated = $request->validate([
            'day_of_week' => 'required|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:240',
        ]);

        $organization->availabilities()->create([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'slot_duration_minutes' => $validated['slot_duration_minutes'] ?? 30,
            'is_active' => true,
        ]);

        return back()->with('success', 'Availability added.');
    }

    public function update(Request $request, Availability $availability)
    {
        if ($availability->organization_id !== current_org_id()) {
            abort(403);
        }

        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:240',
            'is_active' => 'boolean',
        ]);

        $availability->update($validated);

        return back()->with('success', 'Availability updated.');
    }

    public function destroy(Availability $availability)
    {
        if ($availability->organization_id !== current_org_id()) {
            abort(403);
        }

        $availability->delete();

        return back()->with('success', 'Availability removed.');
    }
}
