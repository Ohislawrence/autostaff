<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Organization;
use App\Services\Appointments\AppointmentSlotService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ScheduleAppointmentTool extends BaseTool
{
    protected string $category = 'appointments';

    public function getIdentifier(): string
    {
        return 'schedule_appointment';
    }

    public function getName(): string
    {
        return 'Schedule Appointment';
    }

    public function getDescription(): string
    {
        return 'Schedule a new appointment for a customer within available business hours.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'string', 'description' => 'Customer UUID or ID'],
                'title' => ['type' => 'string', 'description' => 'Title of the appointment'],
                'start_time' => ['type' => 'string', 'description' => 'Start time (ISO 8601 format, e.g. 2026-08-15T10:00:00)'],
                'end_time' => ['type' => 'string', 'description' => 'End time (ISO 8601 format)'],
                'service' => ['type' => 'string', 'description' => 'Service name (optional)'],
                'notes' => ['type' => 'string', 'description' => 'Additional notes (optional)'],
                'location' => ['type' => 'string', 'description' => 'Location or meeting link (optional)'],
            ],
            'required' => ['customer_id', 'title', 'start_time', 'end_time'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'appointment_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'start_time' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $organization = Organization::find($orgId);

        $customer = Customer::where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('uuid', $parameters['customer_id'])->orWhere('id', $parameters['customer_id']))
            ->first();

        if (! $customer) {
            return ['success' => false, 'error' => 'Customer not found.'];
        }

        $start = Carbon::parse($parameters['start_time']);
        $end = Carbon::parse($parameters['end_time']);

        $slot = app(AppointmentSlotService::class)->isSlotAvailable($organization, $start, $end);
        if (! $slot['available']) {
            return ['success' => false, 'error' => $slot['error']];
        }

        $appointment = Appointment::create([
            'organization_id' => $orgId,
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'ai_employee_id' => $parameters['_employee'] ?? null,
            'conversation_id' => $parameters['_conversation'] ?? null,
            'title' => $parameters['title'],
            'start_time' => $start,
            'end_time' => $end,
            'service' => $parameters['service'] ?? null,
            'notes' => $parameters['notes'] ?? null,
            'location' => $parameters['location'] ?? null,
            'timezone' => $organization->timezone ?? 'UTC',
            'status' => 'scheduled',
        ]);

        return [
            'success' => true,
            'appointment_id' => $appointment->id,
            'uuid' => $appointment->uuid,
            'title' => $appointment->title,
            'start_time' => $appointment->start_time->toIso8601String(),
            'message' => "Appointment '{$appointment->title}' scheduled successfully.",
        ];
    }
}
