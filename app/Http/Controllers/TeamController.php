<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Switch the current organization.
     */
    public function switchOrganization(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|integer|exists:organizations,id',
        ]);

        $user = auth()->user();
        $organization = Organization::findOrFail($request->organization_id);

        // Verify user belongs to this organization
        if (! $user->organizations()->where('organization_id', $organization->id)->exists()) {
            abort(403, 'You do not belong to this organization.');
        }

        if (! $organization->is_active) {
            abort(403, 'This organization has been suspended.');
        }

        session(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard')->with('success', "Switched to {$organization->name}.");
    }

    public function index()
    {
        $organization = current_org();
        $team = $organization->users()->with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role ?? $user->getRoleNames()->first(),
                'is_owner' => (bool) ($user->pivot->is_owner ?? false),
                'avatar' => $user->avatar,
                'last_login' => $user->last_login_at?->diffForHumans(),
            ];
        });

        $roles = \Spatie\Permission\Models\Role::where('guard_name', 'web')
            ->whereNotIn('name', ['Platform Owner'])
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]);

        return Inertia::render('Team/Index', [
            'team' => $team,
            'roles' => $roles,
            'invitations' => [], // Placeholder for future email invitations
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $organization = current_org();

        // Verify user belongs to this organization
        if (! $organization->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'User does not belong to this organization.');
        }

        // Don't allow changing your own role
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        // Update role in organization_user pivot
        $organization->users()->updateExistingPivot($user->id, [
            'role' => $request->role,
        ]);

        // Sync Spatie role
        $user->syncRoles([$request->role]);

        return back()->with('success', "{$user->name}'s role updated to {$request->role}.");
    }

    public function add(Request $request)
    {
        $organization = current_org();
        
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

        // If user has no org set as default, make this one default
        if (! $user->organizations()->where('organization_user.is_default', true)->exists()) {
            $user->organizations()->updateExistingPivot($organization->id, ['is_default' => true]);
        }

        return back()->with('success', "{$user->name} added to the team as {$validated['role']}.");
    }

    public function remove(Request $request, User $user)
    {
        $organization = current_org();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot remove yourself.');
        }

        // Don't allow removing the owner
        $pivotData = $organization->users()->where('user_id', $user->id)->first()?->pivot;
        if ($pivotData && $pivotData->is_owner) {
            return back()->with('error', 'Cannot remove the organization owner.');
        }

        $organization->users()->detach($user->id);

        return back()->with('success', "{$user->name} has been removed from the team.");
    }
}