<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\CaptureAffiliateClick::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\AuditLogMiddleware::class,
        ]);

        $middleware->alias([
            'tenant' => \App\Http\Middleware\Tenant\ResolveTenant::class,
            'tenant.api' => \App\Http\Middleware\Tenant\ResolveTenantApi::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.limit' => \App\Http\Middleware\CheckSubscriptionLimits::class,
            'prospecting.access' => \App\Http\Middleware\CheckProspectingAccess::class,
            'auth.api_key' => \App\Http\Middleware\AuthenticateApiKey::class,
            'api_key.scope' => \App\Http\Middleware\CheckApiKeyScope::class,
            'plugin.scope' => \App\Http\Middleware\CheckApiKeyScope::class,
            'plugin.signature' => \App\Http\Middleware\VerifyPluginSignature::class,
        ]);

        // The public chat widget endpoint is exempt from CSRF (called cross-origin
        // by embedded widgets without a session token).
        $middleware->validateCsrfTokens(except: [
            'chat/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();