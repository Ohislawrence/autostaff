<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $organization = current_org();

        $reports = GeneratedReport::where('organization_id', $organization->id)
            ->latest('period_end')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'period' => $r->period,
                'period_start' => $r->period_start->toDateString(),
                'period_end' => $r->period_end->toDateString(),
                'metrics' => $r->metrics,
                'summary_text' => $r->summary_text,
            ])
            ->values();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'report_alerts' => $organization->report_alerts ?? ['enabled' => false, 'frequency' => 'weekly', 'recipients' => []],
        ]);
    }

    public function saveAlerts(Request $request)
    {
        $organization = current_org();

        $validated = $request->validate([
            'enabled' => 'boolean',
            'frequency' => 'nullable|in:daily,weekly,monthly',
            'email' => 'nullable|email',
        ]);

        $alerts = $organization->report_alerts ?? [];
        $alerts['enabled'] = (bool) ($validated['enabled'] ?? false);
        $alerts['frequency'] = $validated['frequency'] ?? 'weekly';
        $alerts['recipients'] = [];
        if (! empty($validated['email'])) {
            $alerts['recipients'] = [$validated['email']];
        }

        $organization->update(['report_alerts' => $alerts]);

        return back()->with('success', 'Report alert preferences saved.');
    }
}