<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
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

        return Inertia::render('Reports/Index', ['reports' => $reports]);
    }
}