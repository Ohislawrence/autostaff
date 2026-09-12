<?php

namespace App\Ai\Tools\BuiltIn;

use App\Ai\Tools\BaseTool;
use App\Services\Mcp\CalendarMeetingService;

class BookMeetingTool extends BaseTool
{
    protected string $identifier = 'book_meeting';
    protected string $name = 'Book Meeting';
    protected string $description = 'Book a meeting on the connected Google Calendar for a prospect or customer.';
    protected string $category = 'prospecting';

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string', 'description' => 'Meeting title'],
                'start_time' => ['type' => 'string', 'description' => 'Start time (ISO 8601)'],
                'end_time' => ['type' => 'string', 'description' => 'End time (ISO 8601)'],
                'attendee_email' => ['type' => 'string', 'description' => 'Attendee email'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['summary', 'start_time', 'end_time'],
        ];
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'event_link' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $parameters): array
    {
        $orgId = app('current_organization_id');
        if (! $orgId) {
            return $this->error('No tenant context available.');
        }

        $result = app(CalendarMeetingService::class)->book(
            $orgId,
            (string) ($parameters['summary'] ?? 'Meeting'),
            (string) ($parameters['start_time'] ?? ''),
            (string) ($parameters['end_time'] ?? ''),
            $parameters['attendee_email'] ?? null,
            $parameters['description'] ?? null,
        );

        if (empty($result['success'])) {
            return $this->error($result['error'] ?? 'Could not book the meeting.');
        }

        return $this->success('Meeting booked on Google Calendar.', [
            'event_link' => $result['event_link'] ?? null,
            'connection' => $result['connection'] ?? null,
        ]);
    }
}
