<?php

namespace Tests\Feature;

use App\Mcp\McpException;
use App\Services\Mcp\McpEndpointValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpEndpointValidatorTest extends TestCase
{
    #[Test] public function it_rejects_loopback_ip()
    {
        $this->expectException(McpException::class);
        (new McpEndpointValidator())->assertSafe('http://127.0.0.1/');
    }

    #[Test] public function it_rejects_private_ip()
    {
        $this->expectException(McpException::class);
        (new McpEndpointValidator())->assertSafe('http://192.168.1.10/');
    }

    #[Test] public function it_rejects_metadata_link_local_ip()
    {
        $this->expectException(McpException::class);
        (new McpEndpointValidator())->assertSafe('http://169.254.169.254/');
    }

    #[Test] public function it_rejects_localhost_hostname()
    {
        $this->expectException(McpException::class);
        (new McpEndpointValidator())->assertSafe('http://localhost/');
    }

    #[Test] public function it_rejects_non_http_scheme()
    {
        $this->expectException(McpException::class);
        (new McpEndpointValidator())->assertSafe('ftp://example.com/');
    }

    #[Test] public function it_allows_public_https_hostname()
    {
        (new McpEndpointValidator())->assertSafe('https://mcp.invalid/');
        $this->addToAssertionCount(1);
    }
}
