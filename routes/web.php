<?php

use App\Http\Controllers\AiEmployeeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Public frontpage (marketing site)
Route::view('/', 'frontpage.home')->name('frontpage.home');
Route::view('/product', 'frontpage.product')->name('frontpage.product');
Route::view('/templates', 'frontpage.templates')->name('frontpage.templates');
Route::view('/pricing', 'frontpage.pricing')->name('frontpage.pricing');
Route::view('/docs', 'frontpage.docs')->name('frontpage.docs');
Route::view('/connect', 'frontpage.integrations')->name('frontpage.integrations');
Route::view('/about', 'frontpage.about')->name('frontpage.about');
Route::view('/services', 'frontpage.services')->name('frontpage.services');
Route::view('/contact', 'frontpage.contact')->name('frontpage.contact');
Route::view('/privacy', 'frontpage.privacy')->name('frontpage.privacy');
Route::view('/terms', 'frontpage.terms')->name('frontpage.terms');

// Public plugin marketplace (browse-only)
Route::get('/marketplace', function () {
    $plugins = \App\Models\Plugin::with('versions')
        ->where('is_published', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get()
        ->map(function ($plugin) {
            $current = $plugin->versions->first(fn ($v) => $v->is_current && $v->status === 'published');

            return [
                'name' => $plugin->name,
                'slug' => $plugin->slug,
                'icon' => $plugin->icon,
                'short_description' => $plugin->short_description,
                'category' => $plugin->category,
                'target_platform' => $plugin->target_platform,
                'author' => $plugin->author,
                'downloads_count' => $plugin->downloads_count,
                'current_version' => $current?->version,
            ];
        });

    return view('frontpage.marketplace', ['plugins' => $plugins]);
})->name('frontpage.marketplace');

// SEO: sitemap.xml
Route::get('/sitemap.xml', function () {
    $pages = [
        ['/', '1.0', 'weekly'],
        ['/product', '0.9', 'weekly'],
        ['/templates', '0.9', 'weekly'],
        ['/pricing', '1.0', 'weekly'],
        ['/docs', '0.6', 'monthly'],
        ['/connect', '0.7', 'monthly'],
        ['/about', '0.5', 'monthly'],
        ['/services', '0.7', 'monthly'],
        ['/contact', '0.7', 'monthly'],
        ['/marketplace', '0.8', 'weekly'],
        ['/privacy', '0.1', 'yearly'],
        ['/terms', '0.1', 'yearly'],
    ];

    $base = rtrim(url('/'), '/');

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($pages as [$path, $priority, $changefreq]) {
        $xml .= '  <url>' . "\n"
            . '    <loc>' . e($base . $path) . '</loc>' . "\n"
            . '    <lastmod>' . now()->toDateString() . '</lastmod>' . "\n"
            . '    <changefreq>' . $changefreq . '</changefreq>' . "\n"
            . '    <priority>' . $priority . '</priority>' . "\n"
            . '  </url>' . "\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap.xml');

// Public unsubscribe (one-click opt-out — no auth required)
Route::get('/prospecting/unsubscribe/{token}', [\App\Http\Controllers\UnsubscribeController::class, '__invoke'])->name('prospecting.unsubscribe');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => inertia('Auth/Login'))->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);

    // Registration
    Route::get('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);

    // Password reset
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])->name('password.store');
});

// Email verification (authenticated but not yet verified)
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/verify-email', [\App\Http\Controllers\Auth\EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [\App\Http\Controllers\Auth\VerifyEmailController::class, '__invoke'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
});

// Authenticated + verified routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Platform Owner routes (no tenant required)
    Route::middleware('role:Platform Owner')->group(function () {
        Route::get('/platform', [\App\Http\Controllers\PlatformController::class, 'dashboard'])->name('platform.dashboard');
        Route::get('/platform/tenants', [\App\Http\Controllers\PlatformController::class, 'tenants'])->name('platform.tenants');
        Route::post('/platform/tenants', [\App\Http\Controllers\PlatformController::class, 'storeTenant'])->name('platform.tenants.store');
        Route::get('/platform/tenants/{organization}', [\App\Http\Controllers\PlatformController::class, 'showTenant'])->name('platform.tenants.show');
        Route::post('/platform/tenants/{organization}/toggle', [\App\Http\Controllers\PlatformController::class, 'toggleTenant'])->name('platform.tenants.toggle');
        Route::post('/platform/tenants/{organization}/plan', [\App\Http\Controllers\PlatformController::class, 'updateTenantPlan'])->name('platform.tenants.plan');
        Route::post('/platform/tenants/{organization}/support', [\App\Http\Controllers\PlatformController::class, 'startSupportSession'])->name('platform.tenants.support');
        Route::post('/platform/tenants/{organization}/budget', [\App\Http\Controllers\PlatformController::class, 'updateBudget'])->name('platform.tenants.budget');
        Route::post('/platform/tenants/{organization}/add-user', [\App\Http\Controllers\PlatformController::class, 'addUser'])->name('platform.tenants.add-user');
        Route::get('/platform/users', [\App\Http\Controllers\PlatformController::class, 'users'])->name('platform.users');
        Route::post('/platform/users/{user}/role', [\App\Http\Controllers\PlatformController::class, 'updateUserRole'])->name('platform.users.role');
        Route::get('/platform/plans', [\App\Http\Controllers\PlatformController::class, 'plans'])->name('platform.plans');
        Route::put('/platform/plans/{plan}', [\App\Http\Controllers\PlatformController::class, 'updatePlan'])->name('platform.plans.update');
        Route::get('/platform/settings', [\App\Http\Controllers\PlatformController::class, 'settings'])->name('platform.settings');
        Route::get('/platform/tax-settings', [\App\Http\Controllers\PlatformController::class, 'taxSettings'])->name('platform.tax-settings');
        Route::put('/platform/tax-settings', [\App\Http\Controllers\PlatformController::class, 'updateTaxSettings'])->name('platform.tax-settings.update');
        Route::get('/platform/invoices', [\App\Http\Controllers\PlatformController::class, 'invoices'])->name('platform.invoices');
        Route::get('/platform/invoices/{invoice}', [\App\Http\Controllers\PlatformController::class, 'showInvoice'])->name('platform.invoices.show');
        Route::post('/platform/invoices/{invoice}/mark-paid', [\App\Http\Controllers\PlatformController::class, 'markInvoicePaid'])->name('platform.invoices.mark-paid');

        // Prospecting Engine (Hunt → Qualify → Outreach → Alert)
        Route::get('/platform/prospecting', [\App\Http\Controllers\Platform\ProspectingController::class, 'dashboard'])->name('platform.prospecting.dashboard');
        Route::get('/platform/prospecting/campaigns', [\App\Http\Controllers\Platform\ProspectingController::class, 'campaigns'])->name('platform.prospecting.campaigns');
        Route::post('/platform/prospecting/campaigns', [\App\Http\Controllers\Platform\ProspectingController::class, 'storeCampaign'])->name('platform.prospecting.campaigns.store');
        Route::put('/platform/prospecting/campaigns/{campaign}', [\App\Http\Controllers\Platform\ProspectingController::class, 'updateCampaign'])->name('platform.prospecting.campaigns.update');
        Route::delete('/platform/prospecting/campaigns/{campaign}', [\App\Http\Controllers\Platform\ProspectingController::class, 'destroyCampaign'])->name('platform.prospecting.campaigns.destroy');
        Route::post('/platform/prospecting/campaigns/{campaign}/toggle', [\App\Http\Controllers\Platform\ProspectingController::class, 'toggleCampaign'])->name('platform.prospecting.campaigns.toggle');
        Route::post('/platform/prospecting/campaigns/{campaign}/hunt', [\App\Http\Controllers\Platform\ProspectingController::class, 'runHunt'])->name('platform.prospecting.campaigns.hunt');
        Route::post('/platform/prospecting/campaigns/{campaign}/qualify', [\App\Http\Controllers\Platform\ProspectingController::class, 'qualifyAll'])->name('platform.prospecting.campaigns.qualify');
        Route::post('/platform/prospecting/campaigns/{campaign}/outreach', [\App\Http\Controllers\Platform\ProspectingController::class, 'runOutreach'])->name('platform.prospecting.campaigns.outreach');
        Route::get('/platform/prospecting/prospects', [\App\Http\Controllers\Platform\ProspectingController::class, 'prospects'])->name('platform.prospecting.prospects');
        Route::post('/platform/prospecting/campaigns/{campaign}/prospects', [\App\Http\Controllers\Platform\ProspectingController::class, 'storeProspect'])->name('platform.prospecting.prospects.store');
        Route::post('/platform/prospecting/campaigns/{campaign}/import', [\App\Http\Controllers\Platform\ProspectingController::class, 'importCsv'])->name('platform.prospecting.prospects.import');
        Route::get('/platform/prospecting/prospects/{prospect}', [\App\Http\Controllers\Platform\ProspectingController::class, 'showProspect'])->name('platform.prospecting.prospects.show');
        Route::post('/platform/prospecting/prospects/{prospect}/qualify', [\App\Http\Controllers\Platform\ProspectingController::class, 'qualifyProspect'])->name('platform.prospecting.prospects.qualify');
        Route::post('/platform/prospecting/prospects/{prospect}/generate', [\App\Http\Controllers\Platform\ProspectingController::class, 'generateProspect'])->name('platform.prospecting.prospects.generate');
        Route::post('/platform/prospecting/prospects/{prospect}/send', [\App\Http\Controllers\Platform\ProspectingController::class, 'sendProspect'])->name('platform.prospecting.prospects.send');
        Route::post('/platform/prospecting/prospects/{prospect}/reply', [\App\Http\Controllers\Platform\ProspectingController::class, 'markReplied'])->name('platform.prospecting.prospects.reply');
        Route::delete('/platform/prospecting/prospects/{prospect}', [\App\Http\Controllers\Platform\ProspectingController::class, 'destroyProspect'])->name('platform.prospecting.prospects.destroy');
        Route::get('/platform/prospecting/settings', [\App\Http\Controllers\Platform\ProspectingSettingsController::class, 'index'])->name('platform.prospecting.settings');
        Route::put('/platform/prospecting/settings', [\App\Http\Controllers\Platform\ProspectingSettingsController::class, 'update'])->name('platform.prospecting.settings.update');
        Route::get('/platform/prospecting/suppression', [\App\Http\Controllers\Platform\ProspectingController::class, 'suppressionList'])->name('platform.prospecting.suppression');
        Route::post('/platform/prospecting/suppression/remove', [\App\Http\Controllers\Platform\ProspectingController::class, 'unsuppress'])->name('platform.prospecting.suppression.remove');
        Route::post('/platform/prospecting/prospects/{prospect}/suppress', [\App\Http\Controllers\Platform\ProspectingController::class, 'suppressProspect'])->name('platform.prospecting.prospects.suppress');
        Route::post('/platform/prospecting/prospects/{prospect}/validate', [\App\Http\Controllers\Platform\ProspectingController::class, 'validateProspect'])->name('platform.prospecting.prospects.validate');

        // AI Platform Management (Priority 2)
        Route::get('/platform/providers', [\App\Http\Controllers\Platform\AiManagementController::class, 'providers'])->name('platform.providers');
        Route::get('/platform/models', [\App\Http\Controllers\Platform\AiManagementController::class, 'models'])->name('platform.models');
        Route::get('/platform/templates', [\App\Http\Controllers\Platform\AiManagementController::class, 'templates'])->name('platform.templates');
        Route::post('/platform/templates', [\App\Http\Controllers\Platform\AiManagementController::class, 'storeTemplate'])->name('platform.templates.store');
        Route::put('/platform/templates/{aiEmployee}', [\App\Http\Controllers\Platform\AiManagementController::class, 'updateTemplate'])->name('platform.templates.update');
        Route::delete('/platform/templates/{aiEmployee}', [\App\Http\Controllers\Platform\AiManagementController::class, 'destroyTemplate'])->name('platform.templates.destroy');
        Route::post('/platform/templates/{aiEmployee}/toggle', [\App\Http\Controllers\Platform\AiManagementController::class, 'toggleTemplate'])->name('platform.templates.toggle');
        Route::get('/platform/tools', [\App\Http\Controllers\Platform\AiManagementController::class, 'tools'])->name('platform.tools');
        Route::put('/platform/tools/{tool}', [\App\Http\Controllers\Platform\AiManagementController::class, 'updateTool'])->name('platform.tools.update');
        Route::get('/platform/features', [\App\Http\Controllers\Platform\AiManagementController::class, 'featureFlags'])->name('platform.features');
        Route::put('/platform/features/{id}', [\App\Http\Controllers\Platform\AiManagementController::class, 'updateFeatureFlag'])->name('platform.features.update');

        // Operations (Priority 3)
        Route::get('/platform/usage', [\App\Http\Controllers\Platform\OperationsController::class, 'usage'])->name('platform.usage');
        Route::get('/platform/health', [\App\Http\Controllers\Platform\OperationsController::class, 'health'])->name('platform.health');
        Route::get('/platform/queues', [\App\Http\Controllers\Platform\OperationsController::class, 'queues'])->name('platform.queues');
        Route::get('/platform/failed-jobs', [\App\Http\Controllers\Platform\OperationsController::class, 'failedJobs'])->name('platform.failed-jobs');
        Route::post('/platform/failed-jobs/{id}/retry', [\App\Http\Controllers\Platform\OperationsController::class, 'retryFailedJob'])->name('platform.failed-jobs.retry');
        Route::post('/platform/failed-jobs/clear', [\App\Http\Controllers\Platform\OperationsController::class, 'clearFailedJobs'])->name('platform.failed-jobs.clear');
        Route::get('/platform/ai-runs', [\App\Http\Controllers\Platform\OperationsController::class, 'aiRuns'])->name('platform.ai-runs');
        Route::get('/platform/ai-runs/{aiRun}', [\App\Http\Controllers\Platform\OperationsController::class, 'aiRunDetail'])->name('platform.ai-runs.show');

        // Extended Management (Priority 4)
        Route::get('/platform/knowledge-processing', [\App\Http\Controllers\Platform\ExtendedManagementController::class, 'knowledgeProcessing'])->name('platform.knowledge');
        Route::get('/platform/integrations', [\App\Http\Controllers\Platform\ExtendedManagementController::class, 'integrations'])->name('platform.integrations');
        Route::get('/platform/integration-docs', fn () => inertia('Platform/IntegrationDocs'))->name('platform.integration-docs');
        Route::get('/platform/announcements', [\App\Http\Controllers\Platform\ExtendedManagementController::class, 'announcements'])->name('platform.announcements');
        Route::post('/platform/announcements', [\App\Http\Controllers\Platform\ExtendedManagementController::class, 'storeAnnouncement'])->name('platform.announcements.store');
        Route::get('/platform/tickets', [\App\Http\Controllers\Platform\ExtendedManagementController::class, 'tickets'])->name('platform.tickets');
        Route::get('/platform/evaluations', [\App\Http\Controllers\Platform\ExtendedManagementController::class, 'evaluations'])->name('platform.evaluations');
        Route::post('/platform/evaluations', [\App\Http\Controllers\Platform\ExtendedManagementController::class, 'storeEvaluation'])->name('platform.evaluations.store');

        // Plugin Catalog
        Route::get('/platform/plugins', [\App\Http\Controllers\Platform\PluginController::class, 'index'])->name('platform.plugins');
        Route::post('/platform/plugins', [\App\Http\Controllers\Platform\PluginController::class, 'store'])->name('platform.plugins.store');
        Route::put('/platform/plugins/{plugin}', [\App\Http\Controllers\Platform\PluginController::class, 'update'])->name('platform.plugins.update');
        Route::delete('/platform/plugins/{plugin}', [\App\Http\Controllers\Platform\PluginController::class, 'destroy'])->name('platform.plugins.destroy');
        Route::post('/platform/plugins/{plugin}/toggle', [\App\Http\Controllers\Platform\PluginController::class, 'toggle'])->name('platform.plugins.toggle');
        Route::post('/platform/plugins/{plugin}/versions', [\App\Http\Controllers\Platform\PluginController::class, 'storeVersion'])->name('platform.plugins.versions.store');
        Route::post('/platform/plugin-versions/{version}/publish', [\App\Http\Controllers\Platform\PluginController::class, 'publishVersion'])->name('platform.plugin-versions.publish');
        Route::post('/platform/plugin-versions/{version}/deprecate', [\App\Http\Controllers\Platform\PluginController::class, 'deprecateVersion'])->name('platform.plugin-versions.deprecate');
        Route::delete('/platform/plugin-versions/{version}', [\App\Http\Controllers\Platform\PluginController::class, 'destroyVersion'])->name('platform.plugin-versions.destroy');

        // Operator Command Center (marketing, tasks, achievements, goals)
        Route::get('/platform/marketing', [\App\Http\Controllers\Platform\GrowthController::class, 'marketing'])->name('platform.marketing');
        Route::post('/platform/marketing/channels', [\App\Http\Controllers\Platform\GrowthController::class, 'storeChannel'])->name('platform.marketing.channels.store');
        Route::put('/platform/marketing/channels/{channel}', [\App\Http\Controllers\Platform\GrowthController::class, 'updateChannel'])->name('platform.marketing.channels.update');
        Route::delete('/platform/marketing/channels/{channel}', [\App\Http\Controllers\Platform\GrowthController::class, 'destroyChannel'])->name('platform.marketing.channels.destroy');
        Route::post('/platform/marketing/channels/{channel}/metrics', [\App\Http\Controllers\Platform\GrowthController::class, 'storeMetric'])->name('platform.marketing.metrics.store');

        Route::get('/platform/tasks', [\App\Http\Controllers\Platform\TaskController::class, 'index'])->name('platform.tasks');
        Route::post('/platform/tasks', [\App\Http\Controllers\Platform\TaskController::class, 'store'])->name('platform.tasks.store');
        Route::post('/platform/tasks/{task}/toggle', [\App\Http\Controllers\Platform\TaskController::class, 'toggle'])->name('platform.tasks.toggle');
        Route::delete('/platform/tasks/{task}', [\App\Http\Controllers\Platform\TaskController::class, 'destroy'])->name('platform.tasks.destroy');

        Route::get('/platform/achievements', [\App\Http\Controllers\Platform\GrowthController::class, 'achievements'])->name('platform.achievements');

        Route::get('/platform/goals', [\App\Http\Controllers\Platform\GrowthController::class, 'goals'])->name('platform.goals');
        Route::post('/platform/goals', [\App\Http\Controllers\Platform\GrowthController::class, 'storeGoal'])->name('platform.goals.store');
        Route::put('/platform/goals/{goal}', [\App\Http\Controllers\Platform\GrowthController::class, 'updateGoal'])->name('platform.goals.update');
        Route::delete('/platform/goals/{goal}', [\App\Http\Controllers\Platform\GrowthController::class, 'destroyGoal'])->name('platform.goals.destroy');
    });

    // Support session exit (accessible by any authenticated user)
    Route::post('/support-session/end', [\App\Http\Controllers\PlatformController::class, 'endSupportSession'])->name('support-session.end');

    // No organization page (redirects to onboarding)
    Route::get('/no-organization', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('no-organization');

    // Onboarding wizard
    Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/create-org', [\App\Http\Controllers\OnboardingController::class, 'createOrganization'])->name('onboarding.create-org');
    Route::post('/onboarding/configure-profile', [\App\Http\Controllers\OnboardingController::class, 'configureProfile'])->name('onboarding.configure-profile');
    Route::post('/onboarding/create-ai-employee', [\App\Http\Controllers\OnboardingController::class, 'createAiEmployee'])->name('onboarding.create-ai-employee');
    Route::post('/onboarding/sales-goal', [\App\Http\Controllers\OnboardingController::class, 'storeSalesGoal'])->name('onboarding.sales-goal');
    Route::post('/onboarding/icp', [\App\Http\Controllers\OnboardingController::class, 'storeIcp'])->name('onboarding.icp');
    Route::post('/onboarding/launch', [\App\Http\Controllers\OnboardingController::class, 'launchCampaign'])->name('onboarding.launch');
    Route::post('/onboarding/complete', [\App\Http\Controllers\OnboardingController::class, 'finish'])->name('onboarding.complete');

    // Switch organization
    Route::post('/switch-organization', [\App\Http\Controllers\TeamController::class, 'switchOrganization'])
        ->name('switch-organization');
    Route::post('/organizations', [\App\Http\Controllers\OrganizationController::class, 'store'])->name('organizations.store');

    // Tenant routes (users belonging to an organization)
    Route::middleware(['tenant'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // AI Employees
        Route::middleware('can:ai-employees.view')->group(function () {
            Route::get('/ai-employees', [AiEmployeeController::class, 'index'])->name('ai-employees.index');
        });
        Route::middleware('can:ai-employees.create')->group(function () {
            Route::get('/ai-employees/create', [AiEmployeeController::class, 'create'])->name('ai-employees.create');
            Route::post('/ai-employees', [AiEmployeeController::class, 'store'])->name('ai-employees.store');
        });

        // AI workforce (recommend + deploy) — registered before {aiEmployee} routes
        Route::middleware('can:ai-employees.view')->group(function () {
            Route::get('/ai-employees/workforce', [AiEmployeeController::class, 'recommendWorkforce'])->name('ai-employees.workforce');
        });
        Route::middleware('can:ai-employees.create')->group(function () {
            Route::post('/ai-employees/workforce/deploy', [AiEmployeeController::class, 'deployWorkforce'])->name('ai-employees.workforce.deploy');
        });
        Route::middleware('can:ai-employees.update')->group(function () {
            Route::get('/ai-employees/{aiEmployee}/edit', [AiEmployeeController::class, 'edit'])->name('ai-employees.edit');
            Route::put('/ai-employees/{aiEmployee}', [AiEmployeeController::class, 'update'])->name('ai-employees.update');
            Route::post('/ai-employees/{aiEmployee}/toggle', [AiEmployeeController::class, 'toggle'])->name('ai-employees.toggle');
        });
        Route::middleware('can:ai-employees.delete')->group(function () {
            Route::delete('/ai-employees/{aiEmployee}', [AiEmployeeController::class, 'destroy'])->name('ai-employees.destroy');
        });
        Route::middleware('can:ai-employees.test')->group(function () {
            Route::post('/ai-employees/{aiEmployee}/test', [AiEmployeeController::class, 'test'])->name('ai-employees.test');
        });

        // Chat widget endpoint (public-facing — no permission required, uses employee UUID)
        Route::post('/chat/{aiEmployee:uuid}/message', [App\Http\Controllers\ChatController::class, 'sendMessage'])
            ->name('chat.send-message');

        // Knowledge Base
        Route::middleware('can:knowledge.view')->group(function () {
            Route::get('/knowledge', [\App\Http\Controllers\KnowledgeController::class, 'index'])->name('knowledge.index');
            Route::post('/knowledge/inspect', [\App\Http\Controllers\KnowledgeController::class, 'inspect'])->name('knowledge.inspect');
        });

        // Knowledge Gaps (learning loop)
        Route::middleware('can:knowledge.view')->group(function () {
            Route::get('/knowledge-gaps', [\App\Http\Controllers\KnowledgeGapController::class, 'index'])->name('knowledge-gaps.index');
            Route::post('/knowledge-gaps/{knowledgeGap}/resolve', [\App\Http\Controllers\KnowledgeGapController::class, 'resolve'])->name('knowledge-gaps.resolve');
        });
        Route::middleware('can:knowledge.create')->group(function () {
            Route::post('/knowledge/bases', [\App\Http\Controllers\KnowledgeController::class, 'storeKnowledgeBase'])->name('knowledge.store-base');
            Route::post('/knowledge/bases/{knowledgeBase}/sources', [\App\Http\Controllers\KnowledgeController::class, 'uploadSource'])->name('knowledge.upload-source');
        });
        Route::middleware('can:knowledge.delete')->group(function () {
            Route::delete('/knowledge/bases/{knowledgeBase}', [\App\Http\Controllers\KnowledgeController::class, 'destroyKnowledgeBase'])->name('knowledge.destroy-base');
            Route::delete('/knowledge/sources/{knowledgeSource}', [\App\Http\Controllers\KnowledgeController::class, 'destroySource'])->name('knowledge.destroy-source');
        });

        // Customers
        Route::middleware('can:customers.view')->group(function () {
            Route::get('/customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'show'])->name('customers.show');
        });
        Route::middleware('can:customers.update')->group(function () {
            Route::put('/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
        });

        // Leads
        Route::middleware('can:leads.view')->group(function () {
            Route::get('/leads', [\App\Http\Controllers\LeadController::class, 'index'])->name('leads.index');
            Route::get('/leads/{lead}', [\App\Http\Controllers\LeadController::class, 'show'])->name('leads.show');
        });
        Route::middleware('can:leads.update')->group(function () {
            Route::put('/leads/{lead}', [\App\Http\Controllers\LeadController::class, 'update'])->name('leads.update');
        });

        // Prospecting (outbound AI Sales Employee — tenant-scoped)
        Route::middleware('can:prospecting.view')->group(function () {
            Route::get('/prospecting', [\App\Http\Controllers\ProspectingController::class, 'index'])->name('prospecting.index');
            Route::get('/prospecting/campaigns', [\App\Http\Controllers\ProspectingController::class, 'campaigns'])->name('prospecting.campaigns');
            Route::get('/prospecting/prospects', [\App\Http\Controllers\ProspectingController::class, 'prospects'])->name('prospecting.prospects');
            Route::get('/prospecting/prospects/{prospect}', [\App\Http\Controllers\ProspectingController::class, 'showProspect'])->name('prospecting.prospects.show');
            Route::get('/prospecting/suppression', [\App\Http\Controllers\ProspectingController::class, 'suppression'])->name('prospecting.suppression');
            Route::get('/prospecting/settings', [\App\Http\Controllers\ProspectingController::class, 'settings'])->name('prospecting.settings');
            Route::get('/prospecting/personas', [\App\Http\Controllers\BuyerPersonaController::class, 'index'])->name('prospecting.personas');
        });
        Route::middleware('can:prospecting.manage')->group(function () {
            Route::put('/prospecting/settings', [\App\Http\Controllers\ProspectingController::class, 'updateSettings'])->name('prospecting.settings.update');
            Route::post('/prospecting/personas/generate', [\App\Http\Controllers\BuyerPersonaController::class, 'generate'])->name('prospecting.personas.generate');
            Route::post('/prospecting/personas/use-template', [\App\Http\Controllers\BuyerPersonaController::class, 'useTemplate'])->name('prospecting.personas.use-template');
            Route::post('/prospecting/personas', [\App\Http\Controllers\BuyerPersonaController::class, 'store'])->name('prospecting.personas.store');
            Route::put('/prospecting/personas/{persona}', [\App\Http\Controllers\BuyerPersonaController::class, 'update'])->name('prospecting.personas.update');
            Route::delete('/prospecting/personas/{persona}', [\App\Http\Controllers\BuyerPersonaController::class, 'destroy'])->name('prospecting.personas.destroy');
            Route::post('/prospecting/campaigns', [\App\Http\Controllers\ProspectingController::class, 'storeCampaign'])->name('prospecting.campaigns.store');
            Route::put('/prospecting/campaigns/{campaign}', [\App\Http\Controllers\ProspectingController::class, 'updateCampaign'])->name('prospecting.campaigns.update');
            Route::delete('/prospecting/campaigns/{campaign}', [\App\Http\Controllers\ProspectingController::class, 'destroyCampaign'])->name('prospecting.campaigns.destroy');
            Route::post('/prospecting/campaigns/{campaign}/toggle', [\App\Http\Controllers\ProspectingController::class, 'toggleCampaign'])->name('prospecting.campaigns.toggle');
            Route::post('/prospecting/campaigns/{campaign}/hunt', [\App\Http\Controllers\ProspectingController::class, 'runHunt'])->name('prospecting.campaigns.hunt');
            Route::post('/prospecting/campaigns/{campaign}/qualify', [\App\Http\Controllers\ProspectingController::class, 'qualifyAll'])->name('prospecting.campaigns.qualify');
            Route::post('/prospecting/campaigns/{campaign}/outreach', [\App\Http\Controllers\ProspectingController::class, 'runOutreach'])->name('prospecting.campaigns.outreach');
            Route::post('/prospecting/campaigns/{campaign}/research', [\App\Http\Controllers\ProspectingController::class, 'researchQualified'])->name('prospecting.campaigns.research');
            Route::post('/prospecting/campaigns/{campaign}/followup', [\App\Http\Controllers\ProspectingController::class, 'runFollowups'])->name('prospecting.campaigns.followup');
            Route::post('/prospecting/prospects/{prospect}/qualify', [\App\Http\Controllers\ProspectingController::class, 'qualifyProspect'])->name('prospecting.prospects.qualify');
            Route::post('/prospecting/prospects/{prospect}/research', [\App\Http\Controllers\ProspectingController::class, 'researchProspect'])->name('prospecting.prospects.research');
            Route::post('/prospecting/prospects/{prospect}/generate', [\App\Http\Controllers\ProspectingController::class, 'generateProspect'])->name('prospecting.prospects.generate');
            Route::post('/prospecting/prospects/{prospect}/send', [\App\Http\Controllers\ProspectingController::class, 'sendProspect'])->name('prospecting.prospects.send');
            Route::post('/prospecting/prospects/{prospect}/suppress', [\App\Http\Controllers\ProspectingController::class, 'suppressProspect'])->name('prospecting.prospects.suppress');
            Route::post('/prospecting/prospects/{prospect}/convert', [\App\Http\Controllers\ProspectingController::class, 'convertProspect'])->name('prospecting.prospects.convert');
            Route::post('/prospecting/prospects/{prospect}/meeting', [\App\Http\Controllers\ProspectingController::class, 'bookMeeting'])->name('prospecting.prospects.meeting');
        });

        // Products
        Route::middleware('can:products.view')->group(function () {
            Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
        });
        Route::middleware('can:products.create')->group(function () {
            Route::post('/products', [\App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
        });
        Route::middleware('can:products.update')->group(function () {
            Route::put('/products/{product}', [\App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
        });
        Route::middleware('can:products.delete')->group(function () {
            Route::delete('/products/{product}', [\App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');
        });

        // Orders
        Route::middleware('can:orders.view')->group(function () {
            Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
        });
        Route::middleware('can:orders.manage-status')->group(function () {
            Route::post('/orders/{order}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('orders.update-status');
        });

        // Appointments
        Route::middleware('can:appointments.view')->group(function () {
            Route::get('/appointments', [\App\Http\Controllers\AppointmentController::class, 'index'])->name('appointments.index');
            Route::get('/appointments/slots', [\App\Http\Controllers\AppointmentController::class, 'getAvailableSlots'])->name('appointments.slots');
        });
        Route::middleware('can:appointments.create')->group(function () {
            Route::get('/appointments/create', [\App\Http\Controllers\AppointmentController::class, 'create'])->name('appointments.create');
            Route::post('/appointments', [\App\Http\Controllers\AppointmentController::class, 'store'])->name('appointments.store');
        });
        Route::middleware('can:appointments.update')->group(function () {
            Route::post('/appointments/{appointment}/update', [\App\Http\Controllers\AppointmentController::class, 'update'])->name('appointments.update');
        });

        // Scheduling settings (business hours + services)
        Route::middleware('can:settings.view')->group(function () {
            Route::get('/settings/scheduling', [\App\Http\Controllers\SettingsController::class, 'scheduling'])->name('settings.scheduling');
            Route::post('/settings/availability', [\App\Http\Controllers\AvailabilityController::class, 'store'])->name('availability.store');
            Route::post('/settings/availability/{availability}', [\App\Http\Controllers\AvailabilityController::class, 'update'])->name('availability.update');
            Route::delete('/settings/availability/{availability}', [\App\Http\Controllers\AvailabilityController::class, 'destroy'])->name('availability.destroy');
            Route::post('/settings/services', [\App\Http\Controllers\ServiceController::class, 'store'])->name('services.store');
            Route::post('/settings/services/{service}', [\App\Http\Controllers\ServiceController::class, 'update'])->name('services.update');
            Route::delete('/settings/services/{service}', [\App\Http\Controllers\ServiceController::class, 'destroy'])->name('services.destroy');
        });

        // Automations
        Route::middleware('can:automations.view')->group(function () {
            Route::get('/automations', [\App\Http\Controllers\AutomationController::class, 'index'])->name('automations.index');
        });
        Route::middleware('can:automations.create')->group(function () {
            Route::post('/automations', [\App\Http\Controllers\AutomationController::class, 'store'])->name('automations.store');
        });
        Route::middleware('can:automations.update')->group(function () {
            Route::put('/automations/{automation}', [\App\Http\Controllers\AutomationController::class, 'update'])->name('automations.update');
            Route::post('/automations/{automation}/toggle', [\App\Http\Controllers\AutomationController::class, 'toggle'])->name('automations.toggle');
        });
        Route::middleware('can:automations.delete')->group(function () {
            Route::delete('/automations/{automation}', [\App\Http\Controllers\AutomationController::class, 'destroy'])->name('automations.destroy');
        });

        // Analytics
        Route::middleware('can:analytics.view')->group(function () {
            Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
        });

        // Reports (scheduled business performance reports)
        Route::middleware('can:analytics.view')->group(function () {
            Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
        });

        // Billing
        Route::middleware('can:billing.view')->group(function () {
            Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
            Route::get('/billing/nomba/callback', [\App\Http\Controllers\BillingController::class, 'nombaCallback'])->name('billing.nomba.callback');
            Route::get('/billing/invoices/{invoice}', [\App\Http\Controllers\BillingController::class, 'invoice'])->name('billing.invoice');
        });
        Route::middleware('can:billing.manage')->group(function () {
            Route::post('/billing/subscribe', [\App\Http\Controllers\BillingController::class, 'subscribe'])->name('billing.subscribe');
            Route::post('/billing/cancel', [\App\Http\Controllers\BillingController::class, 'cancel'])->name('billing.cancel');
        });

        // Files (tenant storage browser)
        Route::middleware('can:settings.view')->group(function () {
            Route::get('/files', [\App\Http\Controllers\KnowledgeController::class, 'files'])->name('files.index');
            Route::get('/files/download', [\App\Http\Controllers\KnowledgeController::class, 'downloadFile'])->name('files.download');
        });

        // Integrations
        Route::middleware('can:ai-employees.manage-channels')->group(function () {
            Route::get('/integrations', fn () => inertia('Integrations/Index'))->name('integrations.index');

            // MCP connections (tenant-scoped external tools)
            Route::get('/integrations/mcp', [\App\Http\Controllers\McpConnectionController::class, 'index'])->name('integrations.mcp.index');
            Route::get('/integrations/mcp/oauth/{provider}/redirect', [\App\Http\Controllers\McpOAuthController::class, 'redirect'])->name('integrations.mcp.oauth.redirect');
            Route::get('/integrations/mcp/oauth/callback', [\App\Http\Controllers\McpOAuthController::class, 'callback'])->name('integrations.mcp.oauth.callback');
            Route::post('/integrations/mcp', [\App\Http\Controllers\McpConnectionController::class, 'store'])->name('integrations.mcp.store');
            Route::delete('/integrations/mcp/{connection}', [\App\Http\Controllers\McpConnectionController::class, 'disconnect'])->name('integrations.mcp.disconnect');
            Route::post('/integrations/mcp/{connection}/health', [\App\Http\Controllers\McpConnectionController::class, 'healthCheck'])->name('integrations.mcp.health');
            Route::post('/integrations/mcp/{connection}/sync', [\App\Http\Controllers\McpConnectionController::class, 'sync'])->name('integrations.mcp.sync');

            Route::get('/integrations/{channel}', [\App\Http\Controllers\IntegrationController::class, 'setup'])->name('integrations.setup');
            Route::post('/integrations/{channel}/save', [\App\Http\Controllers\IntegrationController::class, 'saveConfig'])->name('integrations.save');
            Route::post('/integrations/{channel}/sync', [\App\Http\Controllers\IntegrationController::class, 'sync'])->name('integrations.sync');
            Route::delete('/integrations/{channel}/disconnect', [\App\Http\Controllers\IntegrationController::class, 'disconnect'])->name('integrations.disconnect');
        });

        // Channels (per-AI-employee config)
        Route::get('/ai-employees/{aiEmployee}/channels', [\App\Http\Controllers\Channel\ChannelSetupController::class, 'setup'])->name('channels.setup');
        Route::post('/ai-employees/{aiEmployee}/channels/toggle', [\App\Http\Controllers\Channel\ChannelSetupController::class, 'toggleChannel'])->name('channels.toggle');

        // Inbox (Conversation Hub)
        Route::middleware('can:conversations.view')->group(function () {
            Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
            Route::get('/inbox/{conversation}', [\App\Http\Controllers\InboxController::class, 'show'])->name('inbox.show');
            Route::post('/inbox/{conversation}/reply', [\App\Http\Controllers\InboxController::class, 'sendReply'])->name('inbox.reply');
            Route::post('/inbox/{conversation}/resolve', [\App\Http\Controllers\InboxController::class, 'resolve'])->name('inbox.resolve');
            Route::post('/inbox/{conversation}/assign', [\App\Http\Controllers\InboxController::class, 'assign'])->name('inbox.assign');
        });

        // Approvals (human approval queue for AI tool executions)
        Route::middleware('can:settings.update')->group(function () {
            Route::get('/approvals', [\App\Http\Controllers\ApprovalController::class, 'index'])->name('approvals.index');
            Route::post('/approvals/{toolExecution}/approve', [\App\Http\Controllers\ApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{toolExecution}/deny', [\App\Http\Controllers\ApprovalController::class, 'deny'])->name('approvals.deny');
        });

        // Team
        Route::middleware('can:organization.manage-team')->group(function () {
            Route::get('/team', [\App\Http\Controllers\TeamController::class, 'index'])->name('team.index');
            Route::post('/team/add', [\App\Http\Controllers\TeamController::class, 'add'])->name('team.add');
            Route::post('/team/{user}/role', [\App\Http\Controllers\TeamController::class, 'updateRole'])->name('team.role');
            Route::post('/team/{user}/remove', [\App\Http\Controllers\TeamController::class, 'remove'])->name('team.remove');
        });

        // Help & Documentation
        Route::get('/help', fn () => inertia('Help/Index'))->name('help.index');

        // Announcements
        Route::get('/announcements', fn () => inertia('Announcements/Index'))->name('announcements.index');
        Route::post('/announcements/mark-read/{id}', function ($id) {
            \Illuminate\Support\Facades\DB::table('platform_announcement_reads')->insertOrIgnore([
                'announcement_id' => $id,
                'user_id' => auth()->id(),
                'read_at' => now(),
            ]);
            return back();
        })->name('announcements.mark-read');
        Route::post('/announcements/mark-all-read', function () {
            $announcementIds = \Illuminate\Support\Facades\DB::table('platform_announcements')
                ->where('send_in_app', true)
                ->pluck('id');
            $data = $announcementIds->map(fn ($id) => [
                'announcement_id' => $id,
                'user_id' => auth()->id(),
                'read_at' => now(),
            ])->toArray();
            \Illuminate\Support\Facades\DB::table('platform_announcement_reads')->insertOrIgnore($data);
            return back()->with('success', 'All announcements marked as read.');
        })->name('announcements.mark-all-read');

        // Settings
        Route::middleware('can:settings.view')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('can:settings.update');
        });

        // API Keys (programmatic + plugin access)
        Route::middleware('can:api.manage-keys')->group(function () {
            Route::get('/settings/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'index'])->name('settings.api-keys');
            Route::post('/settings/api-keys', [\App\Http\Controllers\ApiKeyController::class, 'store'])->name('settings.api-keys.store');
            Route::post('/settings/api-keys/{apiKey}/revoke', [\App\Http\Controllers\ApiKeyController::class, 'revoke'])->name('settings.api-keys.revoke');
            Route::post('/settings/api-keys/{apiKey}/regenerate', [\App\Http\Controllers\ApiKeyController::class, 'regenerate'])->name('settings.api-keys.regenerate');
        });

        // Plugin Directory (browse + download)
        Route::get('/plugins', [\App\Http\Controllers\PluginDirectoryController::class, 'index'])->name('plugins.directory');
        Route::get('/plugins/{plugin}/versions/{version}/download', [\App\Http\Controllers\PluginDirectoryController::class, 'download'])->name('plugins.download');

        // Plugin Installations (install + manage)
        Route::get('/plugins/installations', [\App\Http\Controllers\PluginInstallationController::class, 'index'])->name('plugins.installations');
        Route::post('/plugins/{plugin}/install', [\App\Http\Controllers\PluginInstallationController::class, 'store'])->name('plugins.install');
        Route::post('/installations/{installation}/revoke', [\App\Http\Controllers\PluginInstallationController::class, 'revoke'])->name('plugins.installations.revoke');
        Route::post('/installations/{installation}/regenerate', [\App\Http\Controllers\PluginInstallationController::class, 'regenerate'])->name('plugins.installations.regenerate');
    });
});
