<?php

namespace App\Mcp\Transports;

use App\Mcp\McpConnection;
use App\Mcp\McpException;

/**
 * JSON-RPC over stdio (local MCP servers spawned as child processes).
 *
 * Keeps the child process alive across requests so the initialize handshake is
 * preserved for the lifetime of the client.
 */
class StdioTransport implements McpTransportInterface
{
    /** @var resource|null */
    protected $process = null;

    /** @var array<int, resource> */
    protected array $pipes = [];

    protected ?string $stderrPath = null;

    public function __construct(protected McpConnection $connection) {}

    public function send(array $request): array
    {
        if (! is_resource($this->process)) {
            $this->start();
        }

        $requestId = $request['id'] ?? null;

        fwrite($this->pipes[0], json_encode($request) . "\n");

        stream_set_timeout($this->pipes[1], $this->connection->timeout);

        while (($line = fgets($this->pipes[1])) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (! is_array($decoded)) {
                continue;
            }

            // Skip server-initiated notifications (they carry no id).
            if (array_key_exists('id', $decoded) && $decoded['id'] === $requestId) {
                return $decoded;
            }
        }

        $meta = stream_get_meta_data($this->pipes[1]);

        throw new McpException(
            ($meta['timed_out'] ?? false)
                ? 'MCP stdio transport timed out.'
                : 'MCP stdio transport closed before returning a response.'
        );
    }

    protected function start(): void
    {
        $command = $this->connection->command['command'] ?? null;
        if (! $command) {
            throw new McpException('MCP stdio transport requires a command.');
        }

        $args = $this->connection->command['args'] ?? [];

        $this->stderrPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nomdal-mcp-' . uniqid() . '.log';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', $this->stderrPath, 'w'],
        ];

        $env = array_merge(getenv() ?: [], $this->connection->env);

        $this->process = proc_open(
            array_merge([$command], $args),
            $descriptors,
            $this->pipes,
            null,
            $env
        );

        if (! is_resource($this->process)) {
            throw new McpException('Failed to start MCP stdio process.');
        }
    }

    public function __destruct()
    {
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }

        if ($this->stderrPath && is_file($this->stderrPath)) {
            @unlink($this->stderrPath);
        }
    }
}
