<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $organization = current_org();
        $appointments = $organization->appointments()->with(['customer'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->date, fn ($q) => $q->whereDate('start_time', $request->date))
            ->latest('start_time')->paginate(20)->withQueryString();
        $stats = ['today' => $organization->appointments()->whereDate('start_time', today())->count(),'upcoming' => $organization->appointments()->where('start_time', '>', now())->where('status', 'scheduled')->count(),'completed' => $organization->appointments()->where('status', 'completed')->count()];
        return Inertia::render('Appointments/Index', ['appointments' => $appointments, 'stats' => $stats, 'filters' => $request->only(['status', 'date'])]);
    }

    public function create()
    {
        $organization = current_org();
        return Inertia::render('Appointments/Create', ['services' => $organization->services()->where('is_active', true)->get(),'availabilities' => $organization->availabilities()->where('is_active', true)->get(),'customers' => $organization->customers()->select('id', 'first_name', 'last_name')->orderBy('first_name')->get()]);
    }

    public function store(Request $request)
    {
        $organization = current_org();
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'service_id' => 'nullable|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'timezone' => 'nullable|string',
        ]);

        // Handle service_id -> service conversion
        $appointmentData = $validated;
        if (!empty($validated['service_id'])) {
            $service = $organization->services()->find($validated['service_id']);
            $appointmentData['service'] = $service ? $service->name : null;
        }
        unset($appointmentData['service_id']);

        $organization->appointments()->create(array_merge($appointmentData, ['status' => 'scheduled']));
        return redirect()->route('appointments.index')->with('success', 'Appointment scheduled.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $orgId = current_org_id(); if ($appointment->organization_id !== $orgId) abort(403);
        $request->validate(['status' => 'required|in:scheduled,confirmed,cancelled,completed,no_show']);
        $appointment->update(['status' => $request->status]);
        return back()->with('success', 'Appointment updated.');
    }

    public function getAvailableSlots(Request $request)
    {
        $organization = current_org();
        $date = $request->date ? Carbon::parse($request->date) : now();
        $dayOfWeek = strtolower($date->format('l'));
        $availability = $organization->availabilities()->where('day_of_week', $dayOfWeek)->where('is_active', true)->first();
        if (! $availability) return response()->json(['slots' => [], 'message' => 'No availability.']);
        $slots = []; $start = Carbon::parse($date->format('Y-m-d') . ' ' . $availability->start_time);
        $end = Carbon::parse($date->format('Y-m-d') . ' ' . $availability->end_time);
        $duration = $availability->slot_duration_minutes ?? 30;
        while ($start->copy()->addMinutes($duration) <= $end) {
            $slotEnd = $start->copy()->addMinutes($duration);
            $booked = $organization->appointments()->where('status', '!=', 'cancelled')->where(fn ($q) => $q->whereBetween('start_time', [$start, $slotEnd])->orWhereBetween('end_time', [$start, $slotEnd]))->exists();
            if (! $booked) $slots[] = ['start' => $start->format('H:i'), 'end' => $slotEnd->format('H:i'), 'label' => $start->format('g:i A') . ' - ' . $slotEnd->format('g:i A')];
            $start->addMinutes($duration);
        }
        return response()->json(['slots' => $slots, 'date' => $date->format('Y-m-d')]);
    }
}