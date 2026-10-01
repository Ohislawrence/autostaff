<?php

namespace App\Services\Billing;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(protected InvoiceService $invoices) {}

    /**
     * Activate a plan for an organization (cancels any current active sub)
     * and record the paid invoice + payment when money was collected.
     */
    public function activate(Organization $organization, Plan $plan, string $provider = 'nomba', ?array $providerData = null): Subscription
    {
        $organization->subscriptions()
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $subscription = $organization->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'provider' => $provider,
            'provider_subscription_id' => $providerData['reference'] ?? null,
            'provider_data' => $providerData,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $total = (float) ($providerData['total'] ?? 0);

        if ($total > 0) {
            $invoice = $this->invoices->create(
                $organization,
                $subscription,
                "{$plan->name} plan subscription",
                (float) ($providerData['subtotal'] ?? $total),
                (float) ($providerData['tax_amount'] ?? 0),
                $total,
                $providerData['currency'] ?? 'NGN',
                (float) ($providerData['vat_rate'] ?? 0),
                $providerData['taxes'] ?? [],
                $providerData['reference'] ?? null,
            );

            SubscriptionPayment::create([
                'organization_id' => $organization->id,
                'invoice_id' => $invoice->id,
                'provider' => $provider,
                'reference' => $providerData['reference'] ?? null,
                'amount' => $total,
                'currency' => $providerData['currency'] ?? 'NGN',
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        $this->reportAffiliateConversion(
            $organization,
            $plan,
            $total,
            $providerData['currency'] ?? 'NGN',
        );

        return $subscription;
    }

    /**
     * Subscribe an organization to the Free plan (no payment required).
     */
    public function subscribeFree(Organization $organization): Subscription
    {
        $plan = Plan::where('slug', 'free')->where('is_active', true)->first();

        if (! $plan) {
            throw new \RuntimeException('Free plan is not seeded.');
        }

        return $this->activate($organization, $plan, 'free', []);
    }

    /**
     * Activate a pending subscription using its Nomba payment reference.
     */
    public function activateFromReference(string $reference): ?Subscription
    {
        $pending = Subscription::withoutGlobalScope(\App\Tenant\TenantScope::class)
            ->where('provider_subscription_id', $reference)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $pending) {
            return null;
        }

        $planId = $pending->provider_data['plan_id'] ?? null;
        $organizationId = $pending->organization_id;

        $plan = Plan::find($planId);
        $organization = Organization::find($organizationId);

        if (! $plan || ! $organization) {
            Log::warning('Nomba callback: plan or organization missing', [
                'reference' => $reference,
                'plan_id' => $planId,
                'organization_id' => $organizationId,
            ]);

            return null;
        }

        // Transition the pending subscription to active (cancel any current active).
        $organization->subscriptions()->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $pending->update(['status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);

        // Mark its pending invoice paid + record the payment.
        $invoice = SubscriptionInvoice::where('subscription_id', $pending->id)->where('status', 'pending')->first();
        if ($invoice) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            SubscriptionPayment::create([
                'organization_id' => $organization->id,
                'invoice_id' => $invoice->id,
                'provider' => 'nomba',
                'reference' => $reference,
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        $this->reportAffiliateConversion(
            $organization,
            $plan,
            (float) ($pending->provider_data['total'] ?? 0),
            $pending->provider_data['currency'] ?? 'NGN',
        );

        return $pending;
    }

    /**
     * Record a pending (unpaid) subscription before redirecting to Nomba.
     */
    public function createPending(Organization $organization, Plan $plan, string $reference, array $pricing): Subscription
    {
        $subscription = $organization->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'pending',
            'provider' => 'nomba',
            'provider_subscription_id' => $reference,
            'provider_data' => array_merge(['plan_id' => $plan->id, 'reference' => $reference], $pricing),
        ]);

        SubscriptionInvoice::create([
            'organization_id' => $organization->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->invoices->nextNumber(),
            'description' => "{$plan->name} plan subscription",
            'subtotal' => (float) ($pricing['subtotal'] ?? 0),
            'tax_amount' => (float) ($pricing['tax_amount'] ?? 0),
            'total' => (float) ($pricing['total'] ?? 0),
            'currency' => $pricing['currency'] ?? 'NGN',
            'vat_rate' => (float) ($pricing['vat_rate'] ?? 0),
            'taxes' => $pricing['taxes'] ?? [],
            'status' => 'pending',
            'payment_reference' => $reference,
            'due_at' => now()->addMonth(),
        ]);

        return $subscription;
    }

    public function markInvoicePaid(SubscriptionInvoice $invoice): SubscriptionInvoice
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => 'manual-' . $invoice->id,
        ]);

        SubscriptionPayment::create([
            'organization_id' => $invoice->organization_id,
            'invoice_id' => $invoice->id,
            'provider' => 'manual',
            'reference' => 'manual-' . $invoice->id,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'status' => 'success',
            'paid_at' => now(),
        ]);

        if ($subscription = $invoice->subscription) {
            $organization = Organization::find($invoice->organization_id);
            if ($organization) {
                $organization->subscriptions()->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                $subscription->update(['status' => 'active', 'provider' => 'manual', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);

                if ($subscription->plan && $subscription->plan->slug !== 'free') {
                    $this->reportAffiliateConversion($organization, $subscription->plan, (float) $invoice->total, $invoice->currency);
                }
            }
        }

        return $invoice;
    }

    public function recordFailedPayment(Organization $organization, string $reference, float $amount, string $currency, string $error): void
    {
        SubscriptionPayment::create([
            'organization_id' => $organization->id,
            'provider' => 'nomba',
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'failed',
            'error' => $error,
        ]);
    }

    public function recordFailureFromReference(string $reference, string $error): void
    {
        $pending = Subscription::withoutGlobalScope(\App\Tenant\TenantScope::class)
            ->where('provider_subscription_id', $reference)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (! $pending) {
            return;
        }

        $organization = Organization::find($pending->organization_id);
        if (! $organization) {
            return;
        }

        $this->recordFailedPayment(
            $organization,
            $reference,
            (float) ($pending->provider_data['total'] ?? 0),
            $pending->provider_data['currency'] ?? 'NGN',
            $error,
        );
    }

    /**
     * Fire an affiliate conversion (e.g. ClicksIntel postback) when a paid
     * plan is activated. Never throws — attribution/reporting must not block
     * a successful subscription activation.
     */
    protected function reportAffiliateConversion(Organization $organization, Plan $plan, float $total, string $currency): void
    {
        if ($plan->slug === 'free' || $total <= 0) {
            return;
        }

        try {
            app(\App\Services\Affiliate\AffiliateTrackingService::class)
                ->reportConversion($organization, 'paid_subscription', $plan, $total, $currency);
        } catch (\Throwable $e) {
            Log::warning('Affiliate conversion report failed', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
