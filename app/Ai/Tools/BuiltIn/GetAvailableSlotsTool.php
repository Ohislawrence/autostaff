<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Models\Availability;
use Carbon\Carbon;

class GetAvailableSlotsTool extends BaseTool
{
    protected string $category = 'appointments';

    public function getIdentifier(): string
    {
        return 'get_available_slots';
    }

    public function getName(): string
    {
        return 'Get Available Slots';
    }

    public function getDescription(): string
    {
        return 'Check available appointment time slots for a given date.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date' => ['type' => 'string', 'description' => 'Date to check (YYYY-MM-DD format). Defaults to today.'],
                'service' => ['type' => 'string', 'description' => 'Service name to filter by (optional)'],
            ],
            'required' => [],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'date' => ['type' => 'string'],
                'slots' => ['type' => 'array'],
                'available_count' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        $organization = \App\Models\Organization::find($orgId);
        $date = ! empty($parameters['date']) ? Carbon::parse($parameters['date']) : today();
        $dayOfWeek = strtolower($date->format('l'));

        $hasAvailability = $organization->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->exists();

        if (! $hasAvailability) {
            return [
                'success' => true,
                'date' => $date->toDateString(),
                'day_of_week' => $dayOfWeek,
                'slots' => [],
                'message' => "No availability configured for {$dayOfWeek}.",
            ];
        }

        $slots = app(\App\Services\Appointments\AppointmentSlotService::class)->getAvailableSlots($organization, $date);

        return [
            'success' => true,
            'date' => $date->toDateString(),
            'day_of_week' => $dayOfWeek,
            'slots' => $slots,
            'available_count' => count($slots),
            'message' => count($slots) > 0
                ? count($slots)." slots available on {$date->toDateString()}."
                : "No slots available on {$date->toDateString()}.",
        ];
    }
}