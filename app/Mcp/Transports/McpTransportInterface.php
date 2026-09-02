<?php

namespace App\Mcp\Transports;

interface McpTransportInterface
{
    /**
     * Send a JSON-RPC 2.0 request envelope and return the response envelope.
     *
     * @param  array  $request  Full JSON-RPC request (jsonrpc, id, method, params).
     * @return array  Full JSON-RPC response (jsonrpc, id, result|error).
     *
     * @throws \App\Mcp\McpException
     */
    public function send(array $request): array;
}
