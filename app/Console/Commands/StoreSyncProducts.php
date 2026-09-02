<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Commerce\ProductSyncService;
use Illuminate\Console\Command;

class StoreSyncProducts extends Command
{
    protected $signature = 'store:sync-products {organizationId}';

    protected $description = 'Import/sync products from a connected WooCommerce or Shopify store into the local catalog.';

    public function handle(): int
    {
        $organization = Organization::find($this->argument('organizationId'));

        if (! $organization) {
            $this->error('Organization not found.');

            return self::FAILURE;
        }

        $result = app(ProductSyncService::class)->sync($organization);

        if (empty($result['success'])) {
            $this->error('Sync failed: '.($result['error'] ?? 'Unknown error'));

            return self::FAILURE;
        }

        $this->info("Sync complete: {$result['imported']} imported, {$result['updated']} updated.");

        return self::SUCCESS;
    }
}
