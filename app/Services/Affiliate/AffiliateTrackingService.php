<?php

namespace App\Services\Affiliate;

use App\Jobs\Affiliate\SendAffiliatePostback;
use App\Models\AffiliateConversion;
use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AffiliateTrackingService
{
    /**
     * Persist an incoming affiliate click (from the ClicksIntel tracking
     * redirect) into a first-party cookie so it survives navigation to the
     * signup flow. Called from CaptureAffiliateClick middleware.
     */
    public function captureClick(Request $request): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $clickId = $request->query($this->clickParam());

        if (! $clickId) {
            return;
        }

        $value = json_encode([
            'click_id' => $clickId,
            'sub_id' => $request->query($this->subParam()),
            'ts' => now()->timestamp,
        ]);

        Cookie::queue($this->cookieName(), $value, $this->cookieTtlMinutes());
    }

    /**
     * Resolve the affiliate attribution for the current request, preferring
     * explicit query/body params and falling back to the click cookie.
     *
     * @return array{click_id: ?string, sub_id: ?string}
     */
    public function attributionFromRequest(Request $request): array
    {
        $clickId = $request->query($this->clickParam()) ?: $request->input($this->clickParam());
        $subId = $request->query($this->subParam()) ?: $request->input($this->subParam());

        if (! $clickId) {
            $cookie = $this->readCookie($request);
            $clickId = $cookie['click_id'] ?? null;
            $subId = $subId ?: ($cookie['sub_id'] ?? null);
        }

        return [
            'click_id' => $clickId ?: null,
            'sub_id' => $subId ?: null,
        ];
    }

    /**
     * Stamp the affiliate attribution onto a newly created organization.
     * No-op if attribution is disabled, already present, or no click id exists.
     */
    public function attributeOrganization(Organization $organization, Request $request): void
    {
        if (! $this->isEnabled() || $organization->affiliate_click_id) {
            return;
        }

        $attribution = $this->attributionFromRequest($request);

        if (empty($attribution['click_id'])) {
            return;
        }

        $organization->update([
            'affiliate_network' => 'clicksintel',
            'affiliate_click_id' => $attribution['click_id'],
            'affiliate_sub_id' => $attribution['sub_id'],
            'affiliate_referrer' => $request->header('referer'),
        ]);
    }

    /**
     * Record (idempotently) an affiliate conversion and dispatch the postback.
     */
    public function reportConversion(
        Organization $organization,
        string $event,
        ?Plan $plan = null,
        ?float $saleAmount = null,
        ?string $currency = null,
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        $clickId = $organization->affiliate_click_id;

        if (! $clickId) {
            return;
        }

        $network = $organization->affiliate_network ?: 'clicksintel';
        $key = ['network' => $network, 'click_id' => $clickId, 'event' => $event];

        try {
            $conversion = AffiliateConversion::firstOrCreate($key, [
                'organization_id' => $organization->id,
                'sub_id' => $organization->affiliate_sub_id,
                'plan_slug' => $plan?->slug,
                'sale_amount' => $saleAmount,
                'payout_amount' => $this->payoutAmount(),
                'currency' => $currency ?: config('services.clicksintel.payout_currency', 'NGN'),
                'status' => 'pending',
                'attempts' => 0,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $conversion = AffiliateConversion::where($key)->first();
        }

        if (! $conversion) {
            return;
        }

        if ($conversion->wasRecentlyCreated || in_array($conversion->status, ['pending', 'failed'], true)) {
            SendAffiliatePostback::dispatch($conversion->id);
        }
    }

    /**
     * Whether to also report a conversion at the moment of signup (vs. only
     * when a paid plan is activated).
     */
    public function shouldReportSignup(): bool
    {
        return config('services.clicksintel.conversion_event', 'paid_subscription') === 'signup';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.clicksintel.enabled', false);
    }

    protected function clickParam(): string
    {
        return (string) config('services.clicksintel.click_param', 'click_id');
    }

    protected function subParam(): string
    {
        return (string) config('services.clicksintel.sub_param', 'sub_id');
    }

    protected function cookieName(): string
    {
        return (string) config('services.clicksintel.cookie_name', 'ci_click');
    }

    protected function cookieTtlMinutes(): int
    {
        return (int) config('services.clicksintel.cookie_ttl_days', 30) * 24 * 60;
    }

    protected function payoutAmount(): float
    {
        return (float) config('services.clicksintel.payout_amount', 0);
    }

    protected function readCookie(Request $request): array
    {
        $raw = $request->cookie($this->cookieName());

        if (! $raw) {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }
}
