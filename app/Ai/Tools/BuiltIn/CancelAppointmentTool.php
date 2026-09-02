<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Appointment;

class CancelAppointmentTool extends BaseTool
{
    protected string $category = 'appointments';

    public function getIdentifier(): string { return 'cancel_appointment'; }
    public function getName(): string { return 'Cancel Appointment'; }
    public function getDescription(): string { return 'Cancel an existing appointment by ID. Only future appointments can be cancelled.'; }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'appointment_id' => ['type' => 'integer', 'description' => 'ID of the appointment to cancel'],
            ],
            'required' => ['appointment_id'],
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
        $appointment = Appointment::where('organization_id', $orgId)->find($parameters['appointment_id']);

        if (! $appointment) {
            return ['success' => false, 'error' => 'Appointment not found.'];
        }
        if (! in_array($appointment->status, ['scheduled', 'confirmed'])) {
            return ['success' => false, 'error' => 'Only scheduled or confirmed appointments can be cancelled.'];
        }

        $appointment->update(['status' => 'cancelled']);
        return ['success' => true, 'message' => "Appointment '{$appointment->title}' cancelled."];
    }
}