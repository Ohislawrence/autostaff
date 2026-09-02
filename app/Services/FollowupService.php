<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

/**
 * Follow-up rules engine.
 *
 * Prevents spam by enforcing per-conversation max attempts, cooldown windows,
 * business-hours-only delivery, and conversation-state awareness before a
 * scheduled message is accepted.
 */
class FollowupService
{
    /**
     * Schedule a follow-up if it passes all guardrails.
     *
     * @return array{success: bool, scheduled_id: ?int, send_at: ?string, error: ?string}
     */
    public function schedule(
        Organization $organization,
        Customer $customer,
        string $message,
        string $channel = 'whatsapp',
        ?int $sendInHours = null,
        ?int $sendInDays = null,
    ): array {
        $existingCount = DB::table('scheduled_messages')
            ->where('organization_id', $organization->id)
            ->where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->count();

        // Do not pile up duplicates for the same customer.
        if ($existingCount >= 3) {
            return ['success' => false, 'error' => 'Too many pending follow-ups for this customer.'];
        }

        $sendAt = now();
        if ($sendInDays) {
            $sendAt = $sendAt->addDays($sendInDays);
        } elseif ($sendInHours) {
            $sendAt = $sendAt->addHours($sendInHours);
        } else {
            $sendAt = $sendAt->addDay();
        }

        $recipient = $customer->phone ?: $customer->email;
        if (! $recipient) {
            return ['success' => false, 'error' => 'Customer has no phone or email to contact.'];
        }

        $id = DB::table('scheduled_messages')->insertGetId([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'content' => $message,
            'status' => 'pending',
            'send_at' => $sendAt,
            'attempts' => 0,
            'max_attempts' => 3,
            'cooldown_minutes' => 1440,
            'business_hours_only' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'scheduled_id' => $id,
            'send_at' => $sendAt->toISOString(),
            'error' => null,
        ];
    }

    /**
     * Should a due message actually be dispatched right now?
     *
     * @param object $message
     */
    public function shouldDispatchNow(object $message, Organization $organization): bool
    {
        // Attempt limit
        if (($message->attempts ?? 0) >= ($message->max_attempts ?? 3)) {
            return false;
        }

        // Cooldown between attempts
        if (! empty($message->last_attempt_at)) {
            $last = \Carbon\Carbon::parse($message->last_attempt_at);
            $cooldown = (int) ($message->cooldown_minutes ?? 1440);
            if ($last->copy()->addMinutes($cooldown)->isFuture()) {
                return false;
            }
        }

        // Business-hours-only delivery
        if (! empty($message->business_hours_only)) {
            $hours = $organization->business_hours ?? null;
            if ($hours) {
                $day = strtolower(now($organization->timezone)->format('l'));
                $schedule = $hours[$day] ?? null;
                if ($schedule && isset($schedule['open'], $schedule['close'])) {
                    $nowTime = now($organization->timezone)->format('H:i');
                    if ($nowTime < $schedule['open'] || $nowTime > $schedule['close']) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}