<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $organization = current_org();

        $shared = [
            ...parent::share($request),
            'platformAnnouncements' => $request->user()
                ? $this->getActiveAnnouncements()
                : [],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'uuid' => $request->user()->uuid,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar' => $request->user()->avatar,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
                'organization' => $organization ? [
                    'id' => $organization->id,
                    'uuid' => $organization->uuid,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'logo' => $organization->logo,
                    'email' => $organization->email,
                    'phone' => $organization->phone,
                    'website' => $organization->website,
                    'industry' => $organization->industry,
                    'country' => $organization->country,
                    'currency' => $organization->currency,
                    'timezone' => $organization->timezone,
                ] : null,
                'organizations' => $request->user() && !$request->user()->hasRole('Platform Owner') ? $request->user()->organizations()->select('organizations.id', 'organizations.uuid', 'organizations.name', 'organizations.slug', 'organizations.logo')->get() : [],
            ],
            'ziggy' => fn () => [
                'location' => $request->url(),
                'query' => $request->query(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'api_key' => $request->session()->get('api_key'),
                'signing_secret' => $request->session()->get('signing_secret'),
            ],
        ];

        return $shared;
    }

    /**
     * Get active platform announcements for display to tenant users.
     */
    protected function getActiveAnnouncements(): array
    {
        try {
            $userId = auth()->id();

            return \Illuminate\Support\Facades\DB::table('platform_announcements')
                ->where('send_in_app', true)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'body' => $a->body,
                    'type' => $a->type ?? 'info',
                    'published_at' => $a->published_at,
                    'read_at' => $this->getAnnouncementReadStatus($a->id, $userId),
                ])
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if a specific announcement has been read by the current user.
     */
    protected function getAnnouncementReadStatus(int $announcementId, int $userId): ?string
    {
        try {
            $read = \Illuminate\Support\Facades\DB::table('platform_announcement_reads')
                ->where('announcement_id', $announcementId)
                ->where('user_id', $userId)
                ->value('read_at');

            return $read;
        } catch (\Exception $e) {
            return null;
        }
    }
}
