<?php

namespace App\Services\Mcp;

use App\Mcp\McpException;

/**
 * Rejects MCP endpoints that would expose the platform to SSRF (private or
 * reserved network addresses, non-http(s) schemes, or local hostnames).
 */
class McpEndpointValidator
{
    public function assertSafe(?string $url): void
    {
        if (! $url) {
            return;
        }

        $parts = parse_url($url);

        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new McpException('MCP endpoint must use http or https.');
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host === '') {
            throw new McpException('MCP endpoint has no valid host.');
        }

        if ($this->isLocalHost($host)) {
            throw new McpException('MCP endpoint must not point to a local address.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateIp($host)) {
                throw new McpException('MCP endpoint must not point to a private or reserved address.');
            }

            return;
        }

        // Hostname — resolve and reject any private/reserved address.
        foreach (@gethostbynamel($host) ?: [] as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw new McpException('MCP endpoint resolves to a private or reserved address.');
            }
        }
    }

    protected function isLocalHost(string $host): bool
    {
        return $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal');
    }

    protected function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->isPrivateIpv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isPrivateIpv6($ip);
        }

        return false;
    }

    protected function isPrivateIpv4(string $ip): bool
    {
        $long = ip2long($ip);

        if ($long === false) {
            return false;
        }

        return $this->inRange($long, '10.0.0.0', '10.255.255.255')
            || $this->inRange($long, '172.16.0.0', '172.31.255.255')
            || $this->inRange($long, '192.168.0.0', '192.168.255.255')
            || $this->inRange($long, '127.0.0.0', '127.255.255.255')
            || $this->inRange($long, '169.254.0.0', '169.254.255.255')
            || $this->inRange($long, '0.0.0.0', '0.255.255.255');
    }

    protected function isPrivateIpv6(string $ip): bool
    {
        $ip = strtolower($ip);

        return $ip === '::1'
            || $ip === '::'
            || str_starts_with($ip, 'fc')
            || str_starts_with($ip, 'fd')
            || str_starts_with($ip, 'fe80');
    }

    protected function inRange(int $ip, string $start, string $end): bool
    {
        return $ip >= ip2long($start) && $ip <= ip2long($end);
    }
}
