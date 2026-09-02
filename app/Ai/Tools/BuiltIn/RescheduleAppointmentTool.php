<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Appointment;
use App\Models\Organization;
use App\Services\Appointments\AppointmentSlotService;
use Carbon\Carbon;

class RescheduleAppointmentTool extends BaseTool
{
    protected string $category = 'appointments';

    public function getIdentifier(): string { return 'reschedule_appointment'; }
    public function getName(): string { return 'Reschedule Appointment'; }
    public function getDescription(): string { return 'Reschedule an existing appointment to a new available date and time.'; }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'appointment_id' => ['type' => 'integer', 'description' => 'ID of the appointment to reschedule'],
                'new_start_time' => ['type' => 'string', 'description' => 'New start time (ISO 8601 format)'],
                'new_end_time' => ['type' => 'string', 'description' => 'New end time (ISO 8601 format)'],
            ],
            'required' => ['appointment_id', 'new_start_time', 'new_end_time'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $organization = Organization::find($orgId);
        $appointment = Appointment::where('organization_id', $orgId)->find($parameters['appointment_id']);

        if (! $appointment) {
            return ['success' => false, 'error' => 'Appointment not found.'];
        }
        if (! in_array($appointment->status, ['scheduled', 'confirmed'])) {
            return ['success' => false, 'error' => 'Only scheduled or confirmed appointments can be rescheduled.'];
        }

        $start = Carbon::parse($parameters['new_start_time']);
        $end = Carbon::parse($parameters['new_end_time']);

        $slot = app(AppointmentSlotService::class)->isSlotAvailable($organization, $start, $end, $appointment->id);
        if (! $slot['available']) {
            return ['success' => false, 'error' => $slot['error']];
        }

        $appointment->update([
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'scheduled',
        ]);

        return [
            'success' => true,
            'message' => "Appointment '{$appointment->title}' rescheduled to {$appointment->start_time->format('Y-m-d H:i')}.",
        ];
    }
}
