<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogMiddleware
{
    /**
     * Auditable route patterns => event names.
     */
    protected array $auditableRoutes = [
        'login' => 'user_login',
        'logout' => 'user_logout',
        'ai-employees/*/toggle' => 'ai_employee_status_changed',
        'ai-employees/*' => 'ai_employee_modified',
        'knowledge/*' => 'knowledge_modified',
        'automations/*' => 'automation_modified',
        'products/*' => 'product_modified',
        'orders/*/status' => 'order_status_changed',
        'billing/*' => 'billing_changed',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $this->logIfAuditable($request, $response);

        return $response;
    }

    protected function logIfAuditable(Request $request, $response): void
    {
        // Only log write operations (POST, PUT, PATCH, DELETE)
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        $path = $request->path();
        $event = $this->matchEvent($path);

        if (! $event) {
            return;
        }

        try {
            $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;

            AuditLog::create([
                'organization_id' => app()->has('current_organization_id') ? app('current_organization_id') : null,
                'user_id' => Auth::id(),
                'event' => $event,
                'description' => $this->buildDescription($event, $request),
                'new_values' => $request->except(['password', 'password_confirmation', '_token', '_method']),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'method' => $request->method(),
                    'path' => $path,
                    'status_code' => $statusCode,
                ],
            ]);
        } catch (\Exception $e) {
            // Never let audit logging break the request
        }
    }

    protected function matchEvent(string $path): ?string
    {
        foreach ($this->auditableRoutes as $pattern => $event) {
            // Convert route pattern to regex
            $regex = '#^' . str_replace(['*', '/'], ['[^/]+', '\/'], $pattern) . '#'; 
            if (preg_match($regex, $path)) {
                return $event;
            }
        }
        return null;
    }

    protected function buildDescription(string $event, Request $request): string
    {
        $user = Auth::user()?->name ?? 'System';
        $method = $request->method();
        $path = $request->path();

        return match ($event) {
            'user_login' => "{$user} logged in",
            'user_logout' => "{$user} logged out",
            'ai_employee_status_changed' => "{$user} changed AI employee status",
            'ai_employee_modified' => "{$user} {$method} AI employee at {$path}",
            'knowledge_modified' => "{$user} modified knowledge base",
            'automation_modified' => "{$user} modified automation",
            'product_modified' => "{$user} modified product",
            'order_status_changed' => "{$user} changed order status",
            'billing_changed' => "{$user} modified billing",
            default => "{$user} performed {$event} at {$path}",
        };
    }
}