<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Commerce\ProductSyncService;
use Illuminate\Console\Command;

class StoreSyncAllProducts extends Command
{
    protected $signature = 'store:sync-all-products';

    protected $description = 'Sync products for all organizations with a connected WooCommerce/Shopify store.';

    public function handle(): int
    {
        $organizations = Organization::whereHas('integrations', function ($q) {
            $q->whereIn('provider', ['woocommerce', 'shopify'])->where('is_connected', true);
        })->get();

        $service = app(ProductSyncService::class);
        $synced = 0;

        foreach ($organizations as $organization) {
            $result = $service->sync($organization);

            if (empty($result['success'])) {
                $this->warn("Org {$organization->id}: ".($result['error'] ?? 'failed'));
                continue;
            }

            $synced++;
            $this->info("Org {$organization->id}: {$result['imported']} imported, {$result['updated']} updated.");
        }

        $this->info("Completed. {$synced} organization(s) synced.");

        return self::SUCCESS;
    }
}
