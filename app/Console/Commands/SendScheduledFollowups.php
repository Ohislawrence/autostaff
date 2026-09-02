<?php

namespace App\Console\Commands;

use App\Channels\ChannelManager;
use App\Models\Organization;
use App\Services\FollowupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendScheduledFollowups extends Command
{
    protected $signature = 'followups:send';
    protected $description = 'Send scheduled follow-up messages that are due';

    public function handle(FollowupService $followupService): int
    {
        $messages = DB::table('scheduled_messages')
            ->where('status', 'pending')
            ->where('send_at', '<=', now())
            ->get();

        if ($messages->isEmpty()) {
            $this->info('No scheduled messages to send.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($messages as $msg) {
            try {
                $organization = Organization::find($msg->organization_id);
                if (! $organization) {
                    DB::table('scheduled_messages')->where('id', $msg->id)->update(['status' => 'failed', 'error_message' => 'Organization missing', 'updated_at' => now()]);
                    $failed++;
                    continue;
                }

                // Guardrails: attempts, cooldown, business hours.
                if (! $followupService->shouldDispatchNow($msg, $organization)) {
                    $skipped++;
                    continue;
                }

                $channelManager = app(ChannelManager::class);
                $adapter = $channelManager->get($msg->channel);

                $result = $adapter->sendMessage($msg->recipient, $msg->content);

                if ($result['success'] ?? false) {
                    DB::table('scheduled_messages')->where('id', $msg->id)->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'attempts' => ($msg->attempts ?? 0) + 1,
                        'last_attempt_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $sent++;
                } else {
                    $attempts = ($msg->attempts ?? 0) + 1;
                    $final = $attempts >= ($msg->max_attempts ?? 3);
                    DB::table('scheduled_messages')->where('id', $msg->id)->update([
                        'status' => $final ? 'failed' : 'pending',
                        'attempts' => $attempts,
                        'last_attempt_at' => now(),
                        'send_at' => $final ? $msg->send_at : now()->addMinutes((int) ($msg->cooldown_minutes ?? 1440)),
                        'error_message' => $result['error'] ?? 'Send failed',
                        'updated_at' => now(),
                    ]);
                    $failed++;
                }

            } catch (\Exception $e) {
                Log::error('Scheduled message send error', [
                    'message_id' => $msg->id,
                    'error' => $e->getMessage(),
                ]);

                DB::table('scheduled_messages')->where('id', $msg->id)->update([
                    'status' => 'failed',
                    'attempts' => ($msg->attempts ?? 0) + 1,
                    'last_attempt_at' => now(),
                    'error_message' => $e->getMessage(),
                    'updated_at' => now(),
                ]);
                $failed++;
            }
        }

        $this->info("Sent: {$sent}, Failed: {$failed}, Skipped (cooldown/business-hours): {$skipped}");

        return $sent > 0 || $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}