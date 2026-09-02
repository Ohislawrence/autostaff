<?php

namespace App\Channels\Adapters;

use App\Channels\Contracts\ChannelInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailAdapter implements ChannelInterface
{
    public function getIdentifier(): string
    {
        return 'email';
    }

    public function getName(): string
    {
        return 'Email';
    }

    public function processIncoming(array $payload): array
    {
        $from = $payload['from'] ?? '';
        $subject = $payload['subject'] ?? 'No Subject';
        $body = $payload['body'] ?? '';
        $to = $payload['to'] ?? '';

        // Extract customer info from email headers
        preg_match('/<(.+)>/', $from, $matches);
        $email = $matches[1] ?? $from;
        $name = trim(preg_replace('/<.+>/', '', $from)) ?: explode('@', $email)[0];

        return [
            'customer_email' => $email,
            'customer_name' => $name,
            'subject' => $subject,
            'message' => strip_tags($body),
            'channel' => 'email',
            'external_id' => $email,
            'metadata' => [
                'to' => $to,
                'from_raw' => $from,
                'subject' => $subject,
            ],
        ];
    }

    public function sendMessage(string $recipient, string $content, array $metadata = []): array
    {
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name', 'AI Assistant');

        if (! $fromAddress) {
            Log::warning('Email not configured (MAIL_FROM_ADDRESS not set)', ['recipient' => $recipient]);
            return ['success' => false, 'error' => 'Email is not configured. Set MAIL_FROM_ADDRESS in your .env file.'];
        }

        $subject = $metadata['subject'] ?? $metadata['re_subject'] ?? 'Re: Your inquiry';

        try {
            Mail::raw($content, function ($message) use ($recipient, $fromAddress, $fromName, $subject, $metadata) {
                $message->from($fromAddress, $fromName)
                    ->to($recipient)
                    ->subject($subject);

                // If it's a reply, add In-Reply-To and References headers
                if (! empty($metadata['in_reply_to'])) {
                    $message->getHeaders()->addTextHeader('In-Reply-To', $metadata['in_reply_to']);
                }
                if (! empty($metadata['references'])) {
                    $message->getHeaders()->addTextHeader('References', $metadata['references']);
                }
            });

            return [
                'success' => true,
                'channel' => 'email',
                'message_id' => uniqid('email_', true),
                'to' => $recipient,
            ];

        } catch (\Exception $e) {
            Log::error('Email send failed', ['recipient' => $recipient, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        $secret = config('services.email.webhook_secret');

        if (! $secret) {
            // No webhook secret configured — accept incoming (for now)
            return true;
        }

        $signature = $headers['x-webhook-signature'] ?? $headers['x-mailgun-signature'] ?? '';

        return hash_equals(
            hash_hmac('sha256', json_encode($payload), $secret),
            $signature
        );
    }

    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'inbound_driver' => [
                    'type' => 'string',
                    'description' => 'How incoming emails are received',
                    'enum' => ['mailgun', 'sendgrid', 'postmark', 'smtp'],
                ],
                'outbound_driver' => [
                    'type' => 'string',
                    'description' => 'How outgoing emails are sent',
                    'enum' => ['smtp', 'mailgun', 'sendgrid', 'postmark', 'ses'],
                ],
                'from_address' => [
                    'type' => 'string',
                    'description' => 'Email address to send from (e.g. ai@yourbusiness.com)',
                ],
                'from_name' => [
                    'type' => 'string',
                    'description' => 'Display name for outgoing emails (e.g. Your Business AI)',
                ],
                'webhook_secret' => [
                    'type' => 'string',
                    'description' => 'Secret for verifying incoming email webhooks',
                ],
                'auto_reply_enabled' => [
                    'type' => 'boolean',
                    'description' => 'Automatically reply to incoming emails',
                    'default' => true,
                ],
                'signature_html' => [
                    'type' => 'string',
                    'description' => 'HTML email signature appended to AI replies',
                ],
            ],
            'required' => ['from_address'],
        ];
    }

    /**
     * Check if email is available via the configured mail driver.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('mail.from.address')) && ! empty(config('mail.mailer'));
    }
}