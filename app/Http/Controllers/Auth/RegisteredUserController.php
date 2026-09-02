<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        $user->assignRole('Tenant Owner');

        $orgName = $validated['organization'] ?: trim($validated['name']) . "'s Workspace";
        $organization = Organization::create([
            'name' => $orgName,
            'slug' => Str::slug($orgName) . '-' . Str::random(4),
            'onboarding_step' => 'configure_profile',
            'onboarding_completed' => false,
            'is_active' => true,
        ]);

        $organization->users()->attach($user->id, [
            'role' => 'Owner',
            'is_owner' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        session(['current_organization_id' => $organization->id]);

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
