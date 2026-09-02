<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $orgId = session('current_organization_id');
        $organization = $orgId ? Organization::find($orgId) : null;
        $user = $request->user();
        $roles = $user->getRoleNames()->toArray();
        $primaryRole = $roles[0] ?? 'Viewer';

        $dashboardType = match ($primaryRole) {
            'Organization Owner' => 'owner',
            'Tenant Admin' => 'admin',
            'Manager' => 'manager',
            'Agent' => 'agent',
            default => 'viewer',
        };

        $stats = $this->buildStats($organization, $dashboardType);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'dashboardType' => $dashboardType,
            'greeting' => $this->getGreeting($user),
        ]);
    }

    protected function buildStats(?Organization $org, string $type): array
    {
        if (! $org) {
            return ['type' => $type, 'empty' => true];
        }

        $stats = ['type' => $type, 'organization_name' => $org->name];

        // Common KPIs all roles see
        $common = [
            'customers_count' => $org->customers()->count(),
            'leads_count' => $org->leads()->count(),
            'orders_count' => $org->orders()->count(),
            'conversations_count' => $org->conversations()->count(),
            'ai_employees_count' => $org->aiEmployees()->count(),
            'conversations_today' => $org->conversations()->whereDate('created_at', today())->count(),
            'open_conversations' => $org->conversations()->whereIn('status', ['open', 'ai_handling', 'human_required'])->count(),
            'human_required' => $org->conversations()->where('status', 'human_required')->count(),
            'leads_new' => $org->leads()->where('stage', 'new')->count(),
            'leads_won' => $org->leads()->where('stage', 'won')->count(),
            'lead_conversion_rate' => $this->safePercent($org->leads()->where('stage', 'won')->count(), $org->leads()->count()),
            'revenue' => (float) $org->orders()->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total'),
            'orders_this_month' => $org->orders()->whereMonth('created_at', now()->month)->count(),
            'appointments_today' => $org->appointments()->whereDate('start_time', today())->count(),
            'appointments_upcoming' => $org->appointments()->whereIn('status', ['scheduled', 'confirmed'])->where('start_time', '>', now())->count(),
            'resolved_total' => $org->conversations()->where('status', 'resolved')->count(),
            'resolution_rate' => $this->safePercent($org->conversations()->where('status', 'resolved')->count(), $org->conversations()->count()),
        ];

        // Chart trends
        $common['conversations_trend'] = $this->getTrend($org, 'conversations', 7);
        $common['leads_trend'] = $this->getTrend($org, 'leads', 7);
        $common['revenue_trend'] = $this->getRevenueTrend($org, 7);

        // AI Employee performance
        $common['ai_employees'] = $org->aiEmployees()->withCount('conversations')->get()->map(fn ($e) => [
            'name' => $e->name,
            'role' => $e->role ?? 'General',
            'conversations_count' => $e->conversations_count ?? 0,
            'is_active' => $e->is_active,
        ]);

        // Recent activity
        $common['recent_activity'] = $this->getRecentActivity($org, 5);

        // Quick actions
        $common['quick_actions'] = $this->getQuickActions($type, $org);

        // Lead pipeline (for managers/owners)
        $common['leads_pipeline'] = [
            'new' => $org->leads()->where('stage', 'new')->count(),
            'contacted' => $org->leads()->where('stage', 'contacted')->count(),
            'qualified' => $org->leads()->where('stage', 'qualified')->count(),
            'proposal' => $org->leads()->where('stage', 'proposal')->count(),
            'won' => $org->leads()->where('stage', 'won')->count(),
        ];

        return array_merge($stats, $common);
    }

    protected function getTrend(Organization $org, string $model, int $days): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trend[] = [
                'label' => $date->format('M j'),
                'value' => $org->{$model}()->whereDate('created_at', $date)->count(),
            ];
        }
        return $trend;
    }

    protected function getRevenueTrend(Organization $org, int $days): array
    {
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trend[] = [
                'label' => $date->format('M j'),
                'value' => (float) $org->orders()
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->whereDate('created_at', $date)
                    ->sum('total'),
            ];
        }
        return $trend;
    }

    protected function getRecentActivity(Organization $org, int $limit): array
    {
        $activity = [];

        // Recent leads
        foreach ($org->leads()->latest()->take(3)->get() as $lead) {
            $activity[] = [
                'type' => 'lead',
                'icon' => 'bg-blue-100 text-blue-600',
                'icon_svg' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                'message' => "New lead created",
                'detail' => $lead->customer?->first_name ? ($lead->customer->first_name . ' ' . $lead->customer->last_name) : 'Lead #' . $lead->id,
                'time' => $lead->created_at->diffForHumans(),
                'href' => '/leads/' . $lead->id,
            ];
        }

        // Recent orders
        foreach ($org->orders()->latest()->take(3)->get() as $order) {
            $activity[] = [
                'type' => 'order',
                'icon' => 'bg-green-100 text-green-600',
                'icon_svg' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z',
                'message' => 'Order ' . $order->status,
                'detail' => '#' . $order->order_number . ' • ' . format_money($order->total, $order->currency ?? $org->currency),
                'time' => $order->created_at->diffForHumans(),
                'href' => '/orders/' . $order->id,
            ];
        }

        // Conversations needing attention
        $escalated = $org->conversations()->where('status', 'human_required')->latest()->take(2)->get();
        foreach ($escalated as $conv) {
            $activity[] = [
                'type' => 'escalation',
                'icon' => 'bg-orange-100 text-orange-600',
                'icon_svg' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z',
                'message' => 'Conversation needs human',
                'detail' => 'Conv #' . $conv->id,
                'time' => $conv->updated_at->diffForHumans(),
                'href' => '/inbox/' . $conv->id,
            ];
        }

        // Sort by time, take top N
        usort($activity, fn ($a, $b) => strtotime($b['time'] ?? 'now') <=> strtotime($a['time'] ?? 'now'));

        return array_slice($activity, 0, $limit);
    }

    protected function getQuickActions(string $type, Organization $org): array
    {
        $actions = [
            ['label' => 'New AI Employee', 'href' => '/ai-employees/create', 'color' => 'bg-primary-600 hover:bg-primary-700', 'permission' => 'ai-employees.create'],
            ['label' => 'Upload Knowledge', 'href' => '/knowledge', 'color' => 'bg-indigo-600 hover:bg-indigo-700', 'permission' => 'knowledge.create'],
            ['label' => 'View Inbox', 'href' => '/inbox', 'color' => 'bg-orange-600 hover:bg-orange-700', 'permission' => 'conversations.view'],
            ['label' => 'Manage Team', 'href' => '/team', 'color' => 'bg-teal-600 hover:bg-teal-700', 'permission' => 'organization.manage-team'],
            ['label' => 'See Analytics', 'href' => '/analytics', 'color' => 'bg-purple-600 hover:bg-purple-700', 'permission' => 'analytics.view'],
            ['label' => 'Integrations', 'href' => '/integrations', 'color' => 'bg-pink-600 hover:bg-pink-700', 'permission' => 'ai-employees.manage-channels'],
        ];

        return array_values(array_filter($actions, fn ($a) => ! empty($a['permission'])));
    }

    protected function safePercent(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0;
    }

    protected function getGreeting($user): string
    {
        $hour = now()->hour;
        $timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        return "{$timeGreeting}, {$user->name}";
    }
}