<?php

namespace App\Services\Guardrails;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class FallbackManager
{
    /**
     * Get a canned response when the AI provider is unavailable.
     */
    public function getCannedResponse(string $context = 'general'): string
    {
        return match ($context) {
            'timeout' => "I'm experiencing a brief delay ⏳. Please give me a moment — I'll be right back with you. If this is urgent, please call our team directly.",
            'error' => "I'm sorry, I'm having a temporary issue processing your request. Your message has been saved and a member of our team will follow up shortly if needed. 🙏",
            'budget' => "I'm currently operating in limited mode. Please contact our team directly for immediate assistance with this request.",
            'offline' => "Our AI assistant is temporarily offline for maintenance. Your message has been logged and our team will respond as soon as possible. Thank you for your patience! ⚙️",
            default => "I appreciate your message. Due to high demand, I may be a moment. If urgent, please call our team directly. Otherwise, I'll be with you shortly! 😊",
        };
    }

    /**
     * Log a failure and optionally notify the admin.
     */
    public function handleFailure(int $organizationId, string $error, string $context = 'api_call'): void
    {
        Log::error('AI system failure', [
            'organization_id' => $organizationId,
            'error' => $error,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);

        // Notify the platform owner about repeated failures
        $recentFailures = Log::getLogger() ? 1 : 1; // Simple count — in production would query recent logs
        if ($recentFailures >= 3) {
            try {
                Notification::create([
                    'type' => 'system_alert',
                    'title' => 'AI Provider Issues Detected',
                    'body' => "Multiple AI call failures detected (org {$organizationId}). Last error: {$error}",
                    'data' => ['organization_id' => $organizationId, 'error' => $error],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create failure notification', ['error' => $e->getMessage()]);
            }
        }
    }
}