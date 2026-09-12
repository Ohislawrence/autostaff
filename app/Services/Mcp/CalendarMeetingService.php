<?php

namespace App\Services\Mcp;

use App\Mcp\McpTool;
use App\Models\McpConnection;

/**
 * Books a meeting on a tenant's connected Google Calendar via their MCP
 * connection. Gracefully degrades with clear errors when the calendar isn't
 * connected or the server doesn't expose a "create event" tool.
 */
class CalendarMeetingService
{
    public function __construct(
        protected McpConnectionManager $connections,
        protected McpToolInvoker $invoker,
    ) {}

    public function book(int $organizationId, string $summary, string $startTime, string $endTime, ?string $attendeeEmail = null, ?string $description = null): array
    {
        $connection = $this->findCalendarConnection($organizationId);
        if (! $connection) {
            return ['success' => false, 'error' => 'No Google Calendar connected. Connect it under Integrations first.'];
        }

        try {
            $entries = $this->connections->listTools($connection);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Could not reach your Google Calendar: ' . $e->getMessage()];
        }

        $entry = $this->findCreateEventTool($entries);
        if (! $entry) {
            return ['success' => false, 'error' => 'Your Google Calendar connection does not expose a "create event" tool.'];
        }

        $tool = McpTool::fromListEntry($entry);
        $arguments = $this->buildArguments($tool, $summary, $startTime, $endTime, $attendeeEmail, $description);

        try {
            $result = $this->invoker->call($connection, $tool, $arguments);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Booking failed: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'event' => $result,
            'event_link' => $this->extractEventLink($result),
            'connection' => $connection->name,
        ];
    }

    protected function findCalendarConnection(int $organizationId): ?McpConnection
    {
        return McpConnection::forOrganization($organizationId)
            ->whereIn('provider', ['google_calendar', 'google'])
            ->where('is_connected', true)
            ->first();
    }

    protected function findCreateEventTool(array $entries): ?array
    {
        foreach ($entries as $entry) {
            $name = strtolower((string) ($entry['name'] ?? ''));
            if (preg_match('/create[_ -]?event|event[_ -]?create|add[_ -]?event|schedule|create[_ -]?meeting/', $name)) {
                return $entry;
            }
        }

        return null;
    }

    protected function buildArguments(McpTool $tool, string $summary, string $startTime, string $endTime, ?string $attendeeEmail, ?string $description): array
    {
        $props = ($tool->inputSchema ?: ['type' => 'object'])['properties'] ?? [];

        $candidate = [
            'summary' => $summary,
            'title' => $summary,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start' => $startTime,
            'end' => $endTime,
            'attendees' => $attendeeEmail ? [$attendeeEmail] : [],
            'attendee_email' => $attendeeEmail,
            'description' => $description,
        ];

        $arguments = [];
        foreach ($candidate as $key => $value) {
            if (array_key_exists($key, $props) && $value !== null) {
                $arguments[$key] = $value;
            }
        }

        return $arguments;
    }

    protected function extractEventLink(array $result): ?string
    {
        foreach (['htmlLink', 'html_link', 'link', 'url', 'event_url'] as $key) {
            if (! empty($result[$key])) {
                return (string) $result[$key];
            }
        }

        return null;
    }
}
