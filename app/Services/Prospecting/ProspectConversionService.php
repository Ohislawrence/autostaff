<?php

namespace App\Services\Prospecting;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Prospect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Converts an interested prospect into a CRM opportunity (Lead), a service
 * quotation, and an invoice — without requiring a product catalog.
 */
class ProspectConversionService
{
    public function convert(Prospect $prospect, ?float $amount = null): array
    {
        $organization = Organization::find($prospect->organization_id);
        if (! $organization) {
            return ['converted' => false, 'reason' => 'organization missing'];
        }

        $amount = $amount ?? $this->proposalAmount($organization);
        $customer = $this->ensureCustomer($prospect, $organization);
        $lead = $this->createOpportunity($prospect, $customer, $amount);
        $proposal = $this->proposal($organization, $customer, $amount, $prospect->campaign?->offer ?: 'Service');

        $prospect->update([
            'intent' => 'interested',
            'status' => 'converted',
        ]);
        $prospect->events()->create([
            'organization_id' => $organization->id,
            'type' => 'converted',
            'payload' => ['lead_id' => $lead->id, 'amount' => $amount],
        ]);

        return [
            'converted' => true,
            'lead_id' => $lead->id,
            'quotation_id' => $proposal['quotation_id'],
            'invoice_id' => $proposal['invoice_id'],
        ];
    }

    protected function ensureCustomer(Prospect $prospect, Organization $organization): Customer
    {
        $email = strtolower(trim((string) $prospect->email));

        if ($email !== '') {
            $existing = Customer::where('organization_id', $organization->id)->where('email', $email)->first();
            if ($existing) {
                return $existing;
            }
        }

        return Customer::create([
            'organization_id' => $organization->id,
            'first_name' => $prospect->name ?? ($prospect->company ?? 'Prospect'),
            'last_name' => null,
            'email' => $prospect->email,
            'phone' => null,
            'company' => $prospect->company,
            'source' => 'prospecting',
            'notes' => 'Created from outbound prospecting.',
        ]);
    }

    protected function createOpportunity(Prospect $prospect, Customer $customer, float $amount): Lead
    {
        return Lead::create([
            'organization_id' => $prospect->organization_id,
            'customer_id' => $customer->id,
            'ai_employee_id' => $prospect->campaign?->ai_employee_id,
            'stage' => 'qualified',
            'source' => 'prospecting',
            'score' => $prospect->score,
            'estimated_value' => $amount,
            'product_interest' => $prospect->campaign?->offer,
            'notes' => $prospect->reply_summary,
            'metadata' => ['prospect_id' => $prospect->id],
        ]);
    }

    public function proposal(Organization $organization, Customer $customer, float $amount, string $description): array
    {
        $currency = $organization->currency ?: 'NGN';
        $items = [[
            'product_name' => $description,
            'sku' => null,
            'quantity' => 1,
            'unit_price' => (string) $amount,
            'total_price' => (string) $amount,
        ]];

        $quotationId = DB::table('quotations')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'items' => json_encode($items),
            'subtotal' => $amount,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'total' => $amount,
            'currency' => $currency,
            'status' => 'sent',
            'notes' => 'Generated from an interested prospect.',
            'valid_until' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'items' => json_encode($items),
            'subtotal' => $amount,
            'tax_amount' => 0,
            'total' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'notes' => 'Generated from an interested prospect.',
            'due_date' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['quotation_id' => $quotationId, 'invoice_id' => $invoiceId];
    }

    protected function proposalAmount(Organization $organization): float
    {
        $raw = strtolower((string) ($organization->policies['price_point'] ?? ''));
        if ($raw === '') {
            return 0;
        }

        $hasK = str_contains($raw, 'k');
        $cleaned = preg_replace('/[^0-9.]/', '', $raw);
        if ($cleaned === '') {
            return 0;
        }

        $num = (float) $cleaned;
        if ($hasK) {
            $num *= 1000;
        }

        return $num;
    }
}
