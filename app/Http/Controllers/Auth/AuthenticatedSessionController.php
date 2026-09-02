<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        // Platform Owner goes to platform dashboard (no tenant needed)
        if ($user->hasRole('Platform Owner')) {
            return redirect()->intended(route('platform.dashboard'));
        }

        // Set default organization if user has one
        $organization = $user->organizations()->first();
        if ($organization) {
            session(['current_organization_id' => $organization->id]);
            return redirect()->intended(route('dashboard'));
        }

        // User has no organization
        return redirect()->route('no-organization');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}