<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public API v1 for programmatic access to the AI Employee platform.
|
*/

// Health check
Route::get('/health', fn () => response()->json(['status' => 'ok']));

// API v1
Route::prefix('v1')->group(function () {
    // Public webhook endpoints (no auth required, verified by signature)
    Route::match(['get', 'post'], '/webhooks/whatsapp', [\App\Http\Controllers\Api\V1\WebhookController::class, 'whatsapp']);
    Route::post('/webhooks/email', [\App\Http\Controllers\Api\V1\WebhookController::class, 'email']);
    Route::post('/webhooks/woocommerce', [\App\Http\Controllers\Api\V1\WebhookController::class, 'woocommerce']);
    Route::post('/webhooks/shopify', [\App\Http\Controllers\Api\V1\WebhookController::class, 'shopify']);
    Route::post('/webhooks/prospecting-reply/{token}', [\App\Http\Controllers\Api\V1\ProspectingReplyWebhookController::class, '__invoke'])->name('prospecting.reply');
    Route::post('/webhooks/prospecting-delivery/{token}', [\App\Http\Controllers\Api\V1\ProspectingDeliveryWebhookController::class, '__invoke'])->name('prospecting.delivery');
    Route::post('/webhooks/nomba', [\App\Http\Controllers\Api\V1\NombaWebhookController::class, '__invoke'])->name('nomba.webhook');

    // Authenticated API endpoints (with tenant resolution)
    Route::middleware(['auth:sanctum', 'tenant.api'])->group(function () {
        Route::get('/me', fn (Request $request) => $request->user());

        // Messages
        Route::post('/messages', [\App\Http\Controllers\Api\V1\MessageController::class, 'store']);

        // Conversations
        Route::get('/conversations', [\App\Http\Controllers\Api\V1\ConversationController::class, 'index']);
        Route::get('/conversations/{conversation}', [\App\Http\Controllers\Api\V1\ConversationController::class, 'show']);

        // Customers
        Route::get('/customers', [\App\Http\Controllers\Api\V1\CustomerController::class, 'index']);
        Route::post('/customers', [\App\Http\Controllers\Api\V1\CustomerController::class, 'store']);

        // Leads
        Route::get('/leads', [\App\Http\Controllers\Api\V1\LeadController::class, 'index']);
        Route::post('/leads', [\App\Http\Controllers\Api\V1\LeadController::class, 'store']);

        // Orders
        Route::get('/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'index']);
        Route::post('/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'store']);
        Route::get('/orders/{order}', [\App\Http\Controllers\Api\V1\OrderController::class, 'show']);

        // AI Employees
        Route::get('/ai-employees', [\App\Http\Controllers\Api\V1\AiEmployeeController::class, 'index']);
    });

    // Plugin runtime API (rate-limited, authenticated by scoped + signed API keys)
    Route::middleware(['throttle:api-key', 'auth.api_key', 'plugin.signature'])->group(function () {
        Route::post('/plugins/heartbeat', [\App\Http\Controllers\Api\V1\PluginRuntimeController::class, 'heartbeat']);

        Route::get('/customers', [\App\Http\Controllers\Api\V1\CustomerController::class, 'index'])->middleware('api_key.scope:customers:read');
        Route::post('/customers', [\App\Http\Controllers\Api\V1\CustomerController::class, 'store'])->middleware('api_key.scope:customers:create');
        Route::get('/leads', [\App\Http\Controllers\Api\V1\LeadController::class, 'index'])->middleware('api_key.scope:leads:read');
        Route::post('/leads', [\App\Http\Controllers\Api\V1\LeadController::class, 'store'])->middleware('api_key.scope:leads:create');
        Route::get('/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'index'])->middleware('api_key.scope:orders:read');
        Route::post('/orders', [\App\Http\Controllers\Api\V1\OrderController::class, 'store'])->middleware('api_key.scope:orders:create');
        Route::get('/orders/{order}', [\App\Http\Controllers\Api\V1\OrderController::class, 'show'])->middleware('api_key.scope:orders:read');
        Route::post('/messages', [\App\Http\Controllers\Api\V1\MessageController::class, 'store'])->middleware('api_key.scope:messages:send');
        Route::get('/conversations', [\App\Http\Controllers\Api\V1\ConversationController::class, 'index'])->middleware('api_key.scope:conversations:read');
        Route::get('/conversations/{conversation}', [\App\Http\Controllers\Api\V1\ConversationController::class, 'show'])->middleware('api_key.scope:conversations:read');
        Route::get('/ai-employees', [\App\Http\Controllers\Api\V1\AiEmployeeController::class, 'index'])->middleware('api_key.scope:ai-employees:read');
    });
});