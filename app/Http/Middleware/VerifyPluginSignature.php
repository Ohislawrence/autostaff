<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPluginSignature
{
    protected int $windowSeconds = 300;

    public function handle(Request $request, Closure $next): mixed
    {
        $installation = current_plugin_installation();

        // Signing is only enforced when the installation has a signing secret.
        if (! $installation || empty($installation->signing_secret)) {
            return $next($request);
        }

        $timestamp = (int) $request->header('X-Timestamp', 0);
        $signature = (string) $request->header('X-Signature', '');

        if (abs(time() - $timestamp) > $this->windowSeconds) {
            return response()->json(['message' => 'Invalid request timestamp.'], 401);
        }

        $expected = $this->sign($request, $installation->signing_secret, $timestamp);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid request signature.'], 401);
        }

        return $next($request);
    }

    protected function sign(Request $request, string $secret, int $timestamp): string
    {
        $canonical = implode("\n", [
            $request->method(),
            $request->getPathInfo(),
            (string) $timestamp,
            $request->getContent(),
        ]);

        return hash_hmac('sha256', $canonical, $secret);
    }
}
