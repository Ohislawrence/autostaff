<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\SubscriptionInvoice;
use App\Services\Billing\SubscriptionService;
use App\Services\Billing\TaxService;
use App\Services\Payments\NombaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected NombaService $nomba,
    ) {}

    public function index()
    {
        $organization = current_org();
        $subscription = $organization->subscriptions()->with('plan')->latest()->first();
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $usageData = app(\App\Services\Billing\UsageTracker::class)->getLimitsWithUsage($organization);

        $currency = $this->resolveCurrency($organization);
        $rate = (float) config('services.currency.ngn_to_usd', 1500);

        $plans = $plans->map(function (Plan $plan) use ($currency) {
            $price = $plan->priceFor($currency);

            $tax = app(TaxService::class)->calculate($price['amount'], $price['currency']);

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => (float) $plan->price,
                'usd_price' => $plan->usd_price !== null ? (float) $plan->usd_price : null,
                'currency' => $plan->currency,
                'features' => $plan->features,
                'prospecting' => $plan->prospectingLimits(),
                'is_active' => $plan->is_active,
                'display_price' => $price['amount'],
                'display_currency' => $price['currency'],
                'display_total' => $tax['total'],
                'display_tax_amount' => $tax['tax_amount'],
            ];
        })->values();

        $invoices = SubscriptionInvoice::where('organization_id', $organization->id)->latest()->limit(10)->get();
        $settings = PlatformSetting::instance();

        return Inertia::render('Billing/Index', [
            'subscription' => $subscription,
            'plans' => $plans,
            'usage' => $usageData['usage'],
            'limits' => $usageData['limits'],
            'currentPlan' => $usageData['plan'],
            'billingCurrency' => $currency,
            'ngnToUsdRate' => $rate,
            'invoices' => $invoices,
            'manualPaymentEmail' => $settings->manual_payment_email ?: config('mail.from.address'),
            'manualPaymentInstructions' => $settings->manual_payment_instructions,
            'vatRate' => (float) $settings->vat_rate,
        ]);
    }

    public function subscribe(Request $request)
    {
        $organization = current_org();
        $request->validate(['plan_id' => 'required|exists:plans,id']);
        $plan = Plan::findOrFail($request->plan_id);

        $currency = $this->resolveCurrency($organization);
        $price = $plan->priceFor($currency);
        $tax = app(TaxService::class)->calculate($price['amount'], $price['currency']);
        $pricing = [
            'subtotal' => $tax['subtotal'],
            'tax_amount' => $tax['tax_amount'],
            'total' => $tax['total'],
            'currency' => $tax['currency'],
            'vat_rate' => $tax['vat_rate'],
            'taxes' => $tax['taxes'],
        ];

        // Free / custom plan → activate immediately.
        if ($price['amount'] <= 0) {
            $this->subscriptions->activate($organization, $plan, 'manual');

            return back()->with('success', "Subscribed to {$plan->name} plan.");
        }

        // Fallback when Nomba keys aren't set yet (dev/demo).
        if (! $this->nomba->isConfigured()) {
            $this->subscriptions->activate($organization, $plan, 'manual');

            return back()->with('success', "Subscribed to {$plan->name} plan. (Nomba not configured — payment skipped.)");
        }

        $reference = $this->nomba->generateReference('SUB');
        $callback = route('billing.nomba.callback');

        $result = $this->nomba->initializeCheckout([
            'amount' => $tax['total'],
            'currency' => $tax['currency'],
            'email' => $organization->email ?? auth()->user()->email,
            'name' => $organization->name,
            'reference' => $reference,
            'callback_url' => $callback,
            'description' => "{$plan->name} plan subscription",
            'metadata' => ['organization_id' => $organization->id, 'plan_id' => $plan->id],
        ]);

        if (empty($result['success']) || empty($result['checkout_url'])) {
            $this->subscriptions->recordFailedPayment($organization, $reference, $tax['total'], $tax['currency'], $result['error'] ?? 'Nomba checkout failed');

            return back()->with('error', 'Payment could not be started. ' . $this->manualPaymentNotice());
        }

        $this->subscriptions->createPending($organization, $plan, $reference, $pricing);

        return Inertia::location($result['checkout_url']);
    }

    public function nombaCallback(Request $request)
    {
        $reference = $request->get('reference');

        if (! $reference) {
            return redirect()->route('billing.index')->with('error', 'Missing payment reference.');
        }

        $verification = $this->nomba->verifyTransaction($reference);

        if (empty($verification['success'])) {
            return redirect()->route('billing.index')->with('error', 'Could not verify payment. ' . $this->manualPaymentNotice());
        }

        if (empty($verification['verified'])) {
            $this->subscriptions->recordFailedPayment(current_org(), $reference, (float) $verification['amount'], $verification['currency'] ?? 'NGN', 'Payment not successful');

            return redirect()->route('billing.index')->with('error', 'Payment was not successful. ' . $this->manualPaymentNotice());
        }

        $subscription = $this->subscriptions->activateFromReference($reference);

        return redirect()->route('billing.index')->with(
            $subscription ? 'success' : 'error',
            $subscription ? 'Payment successful — subscription activated.' : 'Payment verified but subscription could not be found.'
        );
    }

    public function cancel()
    {
        $organization = current_org();
        $subscription = $organization->subscriptions()->where('status', 'active')->first();
        if ($subscription) {
            $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }

        return back()->with('success', 'Subscription cancelled.');
    }

    public function invoice(SubscriptionInvoice $invoice)
    {
        $organization = current_org();
        if ($invoice->organization_id !== $organization->id) {
            abort(403);
        }

        $settings = PlatformSetting::instance();

        return view('invoices.subscription', [
            'invoice' => $invoice,
            'organization' => $organization,
            'settings' => $settings,
            'plan' => $invoice->subscription?->plan,
        ]);
    }

    protected function manualPaymentNotice(): string
    {
        $settings = PlatformSetting::instance();
        $email = $settings->manual_payment_email ?: config('mail.from.address');
        $instructions = trim((string) $settings->manual_payment_instructions);

        $notice = 'You can also pay manually.';
        if ($instructions !== '') {
            $notice .= ' ' . $instructions;
        }

        return $notice . " Email your payment proof to {$email} and we'll activate your subscription.";
    }

    protected function resolveCurrency(Organization $organization): string
    {
        $currency = strtoupper(trim((string) $organization->currency));

        if (in_array($currency, ['NGN', 'USD'], true)) {
            return $currency;
        }

        $country = strtolower(trim((string) $organization->country));

        return $country === 'nigeria' ? 'NGN' : 'USD';
    }
}
