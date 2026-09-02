<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Organization;
use Carbon\Carbon;

class AppointmentSlotService
{
    /**
     * Compute available booking slots for a given date based on the
     * organization's business hours and existing (non-cancelled) appointments.
     */
    public function getAvailableSlots(Organization $organization, Carbon $date): array
    {
        $dayOfWeek = strtolower($date->format('l'));

        $availabilities = $organization->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($availabilities->isEmpty()) {
            return [];
        }

        $slots = [];

        foreach ($availabilities as $availability) {
            $start = Carbon::parse($availability->start_time);
            $end = Carbon::parse($availability->end_time);
            $duration = $availability->slot_duration_minutes ?: 30;

            while ($start->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $start->copy()->addMinutes($duration);

                $slotStart = $date->copy()->setTimeFromTimeString($start->format('H:i:s'));
                $slotEndDt = $date->copy()->setTimeFromTimeString($slotEnd->format('H:i:s'));

                if (! $this->hasOverlap($organization, $slotStart, $slotEndDt)) {
                    $slots[] = [
                        'start' => $start->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                        'iso_start' => $date->format('Y-m-d').'T'.$start->format('H:i:s'),
                        'duration_minutes' => $duration,
                    ];
                }

                $start->addMinutes($duration);
            }
        }

        return $slots;
    }

    /**
     * Determine whether the requested time window is bookable.
     */
    public function isSlotAvailable(Organization $organization, Carbon $start, Carbon $end, ?int $excludeAppointmentId = null): array
    {
        if ($end->lessThanOrEqualTo($start)) {
            return ['available' => false, 'error' => 'End time must be after start time.'];
        }

        $dayOfWeek = strtolower($start->format('l'));
        $withinHours = $organization->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get()
            ->contains(function ($availability) use ($start, $end) {
                $availStart = Carbon::parse($availability->start_time);
                $availEnd = Carbon::parse($availability->end_time);

                return $start->format('H:i:s') >= $availStart->format('H:i:s')
                    && $end->format('H:i:s') <= $availEnd->format('H:i:s');
            });

        if (! $withinHours) {
            return ['available' => false, 'error' => 'Requested time is outside business hours.'];
        }

        if ($this->hasOverlap($organization, $start, $end, $excludeAppointmentId)) {
            return ['available' => false, 'error' => 'The requested time slot is already booked.'];
        }

        return ['available' => true, 'error' => null];
    }

    protected function hasOverlap(Organization $organization, Carbon $start, Carbon $end, ?int $excludeAppointmentId = null): bool
    {
        return Appointment::where('organization_id', $organization->id)
            ->where('status', '!=', 'cancelled')
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }
}
