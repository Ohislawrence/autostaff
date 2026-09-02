<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    /**
     * Create a new organization and attach the current user as its owner.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        $organization = Organization::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name).'-'.Str::random(4),
            'industry' => $request->industry,
            'onboarding_step' => 'complete',
            'onboarding_completed' => true,
            'is_active' => true,
        ]);

        $organization->users()->attach($user->id, [
            'role' => 'Owner',
            'is_owner' => true,
        ]);

        if (! $user->hasRole('Tenant Owner')) {
            $user->assignRole('Tenant Owner');
        }

        session(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard')->with('success', "Organization '{$organization->name}' created.");
    }
}
