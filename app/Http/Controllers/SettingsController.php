<?php

namespace App\Http\Controllers;

use App\Support\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show the tenant settings page.
     */
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'supportedCurrencies' => Currency::supported(),
        ]);
    }

    /**
     * Show the scheduling settings (business hours + services).
     */
    public function scheduling()
    {
        $organization = current_org();

        return Inertia::render('Settings/Scheduling', [
            'availabilities' => $organization->availabilities()->orderByRaw("CASE day_of_week
                WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 WHEN 'saturday' THEN 6 ELSE 7 END")->get(),
            'services' => $organization->services()->orderBy('name')->get(),
            'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
        ]);
    }

    /**
     * Update the tenant's profile/settings (including currency).
     */
    public function update(Request $request)
    {
        $organization = current_org();
        abort_if(! $organization, 403, 'No active organization.');

        $validated = $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        $organization->update([
            'currency' => Currency::normalize($validated['currency']),
        ]);

        return back()->with('success', 'Settings updated.');
    }
}