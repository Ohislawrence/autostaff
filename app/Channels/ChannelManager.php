<?php

namespace App\Channels;

use App\Channels\Contracts\ChannelInterface;
use App\Models\AiEmployee;
use InvalidArgumentException;

class ChannelManager
{
    protected array $channels = [];

    /**
     * Register a channel adapter.
     */
    public function register(ChannelInterface $channel): void
    {
        $this->channels[$channel->getIdentifier()] = $channel;
    }

    /**
     * Get a channel adapter by identifier.
     */
    public function get(string $identifier): ChannelInterface
    {
        if (! isset($this->channels[$identifier])) {
            throw new InvalidArgumentException("Channel '{$identifier}' is not registered.");
        }

        return $this->channels[$identifier];
    }

    /**
     * Check if a channel is registered.
     */
    public function has(string $identifier): bool
    {
        return isset($this->channels[$identifier]);
    }

    /**
     * Get all registered channels.
     *
     * @return array<string, ChannelInterface>
     */
    public function all(): array
    {
        return $this->channels;
    }

    /**
     * Get channels available for a specific AI employee.
     *
     * @return array<string, ChannelInterface>
     */
    public function getForEmployee(AiEmployee $employee): array
    {
        $allowedChannels = $employee->allowed_channels ?? [];
        if (empty($allowedChannels)) {
            return $this->all();
        }

        return array_intersect_key($this->channels, array_flip($allowedChannels));
    }

    /**
     * Process an incoming message through the appropriate channel.
     */
    public function processIncoming(string $channelIdentifier, array $payload): array
    {
        return $this->get($channelIdentifier)->processIncoming($payload);
    }

    /**
     * Send an outgoing message through a channel.
     */
    public function sendMessage(string $channelIdentifier, string $recipient, string $content, array $metadata = []): array
    {
        return $this->get($channelIdentifier)->sendMessage($recipient, $content, $metadata);
    }
}