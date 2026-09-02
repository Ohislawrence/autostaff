<?php

namespace App\Providers;

use App\Ai\ContextBuilder\AiContextBuilder;
use App\Ai\ContextBuilder\CustomerContextService;
use App\Ai\Orchestrator\AiOrchestrator;
use App\Ai\Understanding\IntentService;
use App\Ai\Providers\DeepSeekProvider;
use App\Ai\Providers\AiProviderInterface;
use App\Ai\Tools\ToolExecutor;
use App\Ai\Tools\ToolRegistry;
use App\Ai\Workflow\PolicyService;
use App\Ai\Workflow\WorkflowStateMachine;
use App\Automation\ActionRegistry;
use App\Automation\TriggerRegistry;
use App\Services\ConversationService;
use App\Services\Guardrails\CostGuardService;
use App\Services\Guardrails\FallbackManager;
use App\Services\Mcp\McpConnectionManager;
use App\Services\Mcp\McpToolRegistrar;
use App\Services\Automation\AutomationService;
use App\Services\Crm\LeadScoringService;
use App\Services\FollowupService;
use App\Services\HandoffService;
use App\Services\Platform\PlatformStatsService;
use App\Services\ReportingService;
use App\Services\Knowledge\ChunkingService;
use App\Services\Knowledge\DocumentProcessor;
use App\Channels\Adapters\EmailAdapter;
use App\Channels\Adapters\ShopifyAdapter;
use App\Channels\Adapters\WebChatAdapter;
use App\Channels\Adapters\WhatsAppAdapter;
use App\Channels\Adapters\WooCommerceAdapter;
use App\Channels\ChannelManager;
use App\Services\Knowledge\KnowledgeGapService;
use App\Services\Knowledge\KnowledgeRagService;
use App\Services\Commerce\StoreConnector;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind AI provider interface to DeepSeek implementation
        $this->app->singleton(AiProviderInterface::class, function ($app) {
            return new DeepSeekProvider();
        });

        // Register tool system
        $this->app->singleton(ToolRegistry::class, function ($app) {
            $registry = new ToolRegistry();
            $registry->register('search_products', \App\Ai\Tools\BuiltIn\SearchProductsTool::class);
            $registry->register('get_product', \App\Ai\Tools\BuiltIn\GetProductTool::class);
            $registry->register('get_price', \App\Ai\Tools\BuiltIn\GetPriceTool::class);
            $registry->register('check_inventory', \App\Ai\Tools\BuiltIn\CheckInventoryTool::class);
            $registry->register('create_lead', \App\Ai\Tools\BuiltIn\CreateLeadTool::class);
            $registry->register('create_customer', \App\Ai\Tools\BuiltIn\CreateCustomerTool::class);
            $registry->register('get_customer', \App\Ai\Tools\BuiltIn\GetCustomerTool::class);
            $registry->register('create_order', \App\Ai\Tools\BuiltIn\CreateOrderTool::class);
            $registry->register('get_order', \App\Ai\Tools\BuiltIn\GetOrderTool::class);
            $registry->register('get_order_status', \App\Ai\Tools\BuiltIn\GetOrderStatusTool::class);
            $registry->register('cancel_order', \App\Ai\Tools\BuiltIn\CancelOrderTool::class);
            $registry->register('transfer_to_human', \App\Ai\Tools\BuiltIn\TransferToHumanTool::class);
            $registry->register('create_task', \App\Ai\Tools\BuiltIn\CreateTaskTool::class);
            $registry->register('schedule_appointment', \App\Ai\Tools\BuiltIn\ScheduleAppointmentTool::class);
            $registry->register('get_available_slots', \App\Ai\Tools\BuiltIn\GetAvailableSlotsTool::class);
            $registry->register('cancel_appointment', \App\Ai\Tools\BuiltIn\CancelAppointmentTool::class);
            $registry->register('reschedule_appointment', \App\Ai\Tools\BuiltIn\RescheduleAppointmentTool::class);
            $registry->register('knowledge_search', \App\Ai\Tools\BuiltIn\KnowledgeSearchTool::class);
            $registry->register('generate_quotation', \App\Ai\Tools\BuiltIn\GenerateQuotationTool::class);
            $registry->register('generate_invoice', \App\Ai\Tools\BuiltIn\GenerateInvoiceTool::class);
            $registry->register('record_payment', \App\Ai\Tools\BuiltIn\RecordPaymentTool::class);
            $registry->register('send_followup', \App\Ai\Tools\BuiltIn\SendFollowupTool::class);
            $registry->register('extract_document_data', \App\Ai\Tools\BuiltIn\ExtractDocumentDataTool::class);
            $registry->register('generate_report', \App\Ai\Tools\BuiltIn\GenerateReportTool::class);
            $registry->register('add_to_cart', \App\Ai\Tools\BuiltIn\AddToCartTool::class);
            $registry->register('get_cart', \App\Ai\Tools\BuiltIn\GetCartTool::class);
            $registry->register('apply_discount', \App\Ai\Tools\BuiltIn\ApplyDiscountTool::class);
            $registry->register('track_shipment', \App\Ai\Tools\BuiltIn\TrackShipmentTool::class);
            $registry->register('create_ticket', \App\Ai\Tools\BuiltIn\CreateTicketTool::class);
            $registry->register('update_ticket', \App\Ai\Tools\BuiltIn\UpdateTicketTool::class);
            $registry->register('search_store', \App\Ai\Tools\BuiltIn\SearchStoreTool::class);
            $registry->register('create_store_order', \App\Ai\Tools\BuiltIn\CreateStoreOrderTool::class);
            $registry->register('hunt_icp', \App\Ai\Tools\BuiltIn\HuntIcpTool::class);
            $registry->register('qualify_prospect', \App\Ai\Tools\BuiltIn\QualifyProspectTool::class);
            $registry->register('draft_outreach', \App\Ai\Tools\BuiltIn\DraftOutreachTool::class);
            $registry->register('send_outreach', \App\Ai\Tools\BuiltIn\SendOutreachTool::class);
            $registry->register('get_prospecting_status', \App\Ai\Tools\BuiltIn\GetProspectingStatusTool::class);

            $registry->setDynamicResolver(function (string $identifier, ?int $organizationId) {
                return app(McpToolRegistrar::class)->resolve($identifier, $organizationId);
            });

            return $registry;
        });

        $this->app->singleton(WorkflowStateMachine::class);
        $this->app->singleton(PolicyService::class);

        $this->app->singleton(ToolExecutor::class, function ($app) {
            return new ToolExecutor(
                $app->make(ToolRegistry::class),
                $app->make(WorkflowStateMachine::class),
                $app->make(PolicyService::class),
            );
        });

        // Knowledge services
        $this->app->singleton(DocumentProcessor::class);
        $this->app->singleton(ChunkingService::class);

        $this->app->singleton(KnowledgeRagService::class, function ($app) {
            return new KnowledgeRagService(
                $app->make(AiProviderInterface::class),
                $app->make(ChunkingService::class),
            );
        });

        $this->app->singleton(KnowledgeGapService::class);

        // Channel system
        $this->app->singleton(ChannelManager::class, function ($app) {
            $manager = new ChannelManager();
        $manager->register(new WebChatAdapter());
        $manager->register(new WhatsAppAdapter());
        $manager->register(new EmailAdapter());
        $manager->register(new WooCommerceAdapter());
        $manager->register(new ShopifyAdapter());
            return $manager;
        });

        // Platform services
        $this->app->singleton(PlatformStatsService::class);

        $this->app->singleton(ReportingService::class);

        // Automation services
        $this->app->singleton(TriggerRegistry::class, function ($app) {
            $registry = new TriggerRegistry();
            $registry->boot();
            return $registry;
        });

        $this->app->singleton(ActionRegistry::class, function ($app) {
            $registry = new ActionRegistry();
            $registry->boot();
            return $registry;
        });

        $this->app->singleton(AutomationService::class);

        // CRM services
        $this->app->singleton(LeadScoringService::class);

        // AI services
        $this->app->singleton(ConversationService::class);

        // Guardrails
        $this->app->singleton(CostGuardService::class);
        $this->app->singleton(FallbackManager::class);

        // MCP
        $this->app->singleton(McpConnectionManager::class);
        $this->app->singleton(McpToolRegistrar::class);

        $this->app->singleton(HandoffService::class);

        $this->app->singleton(FollowupService::class);

        $this->app->singleton(StoreConnector::class);

        $this->app->singleton(CustomerContextService::class);

        $this->app->singleton(IntentService::class, function ($app) {
            return new IntentService($app->make(AiProviderInterface::class));
        });

        $this->app->singleton(AiContextBuilder::class, function ($app) {
            return new AiContextBuilder(
                $app->make(KnowledgeRagService::class),
                $app->make(CustomerContextService::class),
            );
        });

        $this->app->singleton(AiOrchestrator::class, function ($app) {
            return new AiOrchestrator(
                $app->make(AiProviderInterface::class),
                $app->make(AiContextBuilder::class),
                $app->make(ConversationService::class),
                $app->make(ToolExecutor::class),
                $app->make(IntentService::class),
                $app->make(KnowledgeGapService::class),
                $app->make(CostGuardService::class),
                $app->make(FallbackManager::class),
            );
        });
    }

    public function boot(): void
    {
        RateLimiter::for('api-key', function (Request $request) {
            $header = $request->header('Authorization', '');
            $prefix = str_starts_with(strtolower($header), 'bearer ')
                ? substr($header, 7, 20)
                : ($request->ip() ?? 'unknown');

            return Limit::perMinute(120)->by($prefix);
        });
    }
}