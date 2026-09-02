@extends('frontpage.layouts.app')

@section('title', 'Pricing — Nomdal')
@section('meta_description', 'Simple, transparent Nomdal pricing. Plans start at ₦45,000 / $29 per month. Pay in Naira or Dollars.')

@section('content')
    {{-- Hero + plans --}}
    <section x-data="{ annual: false }" class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal text-center">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Pricing</p>
            <h1 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Simple, transparent pricing</h1>
            <p class="mx-auto mt-4 max-w-xl text-ink-dim">Start free. Upgrade as your AI workforce grows. Pay in Naira or Dollars.</p>

            <div class="mt-8 inline-flex items-center rounded-full border border-ink/15 bg-white/40 p-1">
                <button @click="annual = false" :class="!annual ? 'bg-ink text-bone' : 'text-ink-dim'" class="rounded-full px-5 py-2 text-sm font-bold transition">Monthly</button>
                <button @click="annual = true" :class="annual ? 'bg-ink text-bone' : 'text-ink-dim'" class="inline-flex items-center gap-2 rounded-full px-5 py-2 text-sm font-bold transition">
                    Annual
                    <span class="rounded-full bg-lime px-2 py-0.5 text-xs font-bold text-ink">−17%</span>
                </button>
            </div>
        </div>

        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @php $plans = [
                ['Starter', '₦45,000', '$29', '₦37,500', '$24', 'For solo entrepreneurs testing AI.', ['1 AI Employee', 'Web Chat only', '500 messages / month', '1 Knowledge Base (50 sources)', 'Basic CRM (lead tracking)', '5 automations', '5 team members'], false, 'bg-white/40'],
                ['Business', '₦150,000', '$99', '₦125,000', '$82', 'For growing businesses with multi-channel needs.', ['3 AI Employees', 'All Channels (Web, Email, WhatsApp)', '3,000 messages / month', 'Full CRM (scoring & pipeline)', 'Appointments & Commerce', '20 automations', '15 team members'], true, 'bg-ink'],
                ['Professional', '₦375,000', '$249', '₦312,500', '$208', 'For established businesses and agencies.', ['10 AI Employees', 'All Channels + Webhooks', '10,000 messages / month', 'Custom Tools + REST API', '50 automations', '50 team members', '99.5% SLA'], false, 'bg-white/40'],
                ['Enterprise', 'Custom', '', 'Custom', '', 'For large organizations. Contact sales.', ['Unlimited AI Employees', 'Unlimited messages & tools', 'White-label (add-on)', 'Custom integrations', 'Dedicated infrastructure', '500+ team members', '99.9% SLA'], false, 'bg-purple/60'],
            ]; @endphp

            @foreach ($plans as [$name, $price, $usd, $annual, $annualUsd, $tagline, $features, $featured, $bg])
                <div class="reveal relative flex flex-col rounded-3xl border {{ $featured ? 'border-ink' : 'border-ink/10' }} {{ $bg }} p-8">
                    @if ($featured)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-lime px-3 py-1 text-xs font-bold text-ink">Most popular</span>
                    @endif
                    <h3 class="text-lg font-bold {{ $featured ? 'text-bone' : 'text-ink' }}">{{ $name }}</h3>

                    @if ($name === 'Enterprise')
                        <div class="mt-4 font-display text-4xl font-black text-ink">Custom</div>
                    @else
                        <div x-show="!annual" class="mt-4 flex items-baseline gap-1">
                            <span class="font-display text-4xl font-black {{ $featured ? 'text-bone' : 'text-ink' }}">{{ $price }}</span>
                            <span class="text-sm font-semibold {{ $featured ? 'text-bone/60' : 'text-ink-faint' }}">/ {{ $usd }} mo</span>
                        </div>
                        <div x-show="annual" x-cloak class="mt-4">
                            <div class="flex items-baseline gap-1">
                                <span class="font-display text-4xl font-black {{ $featured ? 'text-bone' : 'text-ink' }}">{{ $annual }}</span>
                                <span class="text-sm font-semibold {{ $featured ? 'text-bone/60' : 'text-ink-faint' }}">/ {{ $annualUsd }} mo</span>
                            </div>
                            <p class="mt-1 text-xs {{ $featured ? 'text-bone/60' : 'text-ink-faint' }}">billed annually</p>
                        </div>
                    @endif

                    <p class="mt-2 text-sm {{ $featured ? 'text-bone/70' : 'text-ink-faint' }}">{{ $tagline }}</p>
                    <ul class="mt-6 space-y-3 text-sm {{ $featured ? 'text-bone/80' : 'text-ink-dim' }}">
                        @foreach ($features as $feature)
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-lime">✓</span> {{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('login') }}" class="mt-8 block rounded-full {{ $featured ? 'bg-lime text-ink hover:bg-citron' : 'bg-ink text-bone hover:bg-forest' }} px-5 py-2.5 text-center text-sm font-bold transition">Get started</a>
                </div>
            @endforeach
        </div>

        <p class="reveal mt-8 text-center text-sm text-ink-faint">No credit card required · Cancel anytime · 24/7 support on all plans</p>
    </section>
    {{-- Comparison --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">Compare plans</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Every feature, side by side</h2>
            </div>

            <div class="reveal mt-12 overflow-x-auto">
                <table class="w-full min-w-[720px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink/10">
                            <th class="py-4 pr-6 font-semibold text-ink-faint">Feature</th>
                            <th class="px-4 py-4 font-bold text-ink">Starter</th>
                            <th class="px-4 py-4 font-bold text-ink">Business</th>
                            <th class="px-4 py-4 font-bold text-ink">Professional</th>
                            <th class="px-4 py-4 font-bold text-ink">Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $compare = [
                            ['AI Employees', '1', '3', '10', 'Unlimited'],
                            ['Messages / month', '500', '3,000', '10,000', 'Unlimited'],
                            ['Channels', 'Web chat', 'Web, Email, WhatsApp', '+ Webhooks', 'All + custom'],
                            ['Knowledge sources', '50', '150', '500', '1,000'],
                            ['CRM', 'Basic', 'Full', 'Full', 'Full'],
                            ['Appointments', '—', '✓', '✓', '✓'],
                            ['Commerce (Shopify/Woo)', '—', '✓', '✓', '✓'],
                            ['Automations', '5', '20', '50', 'Unlimited'],
                            ['Team members', '5', '15', '50', '500+'],
                            ['REST API', '—', '—', '✓', '✓'],
                            ['White-label', '—', '—', '—', 'Add-on'],
                            ['Support', 'Email', 'Priority', 'Dedicated', '24/7 phone'],
                            ['SLA', '—', '—', '99.5%', '99.9%'],
                        ]; @endphp
                        @foreach ($compare as $row)
                            <tr class="border-b border-ink/5">
                                <td class="py-3 pr-6 font-medium text-ink-dim">{{ $row[0] }}</td>
                                <td class="px-4 py-3 text-ink-dim">{{ $row[1] }}</td>
                                <td class="px-4 py-3 text-ink-dim">{{ $row[2] }}</td>
                                <td class="px-4 py-3 text-ink-dim">{{ $row[3] }}</td>
                                <td class="px-4 py-3 text-ink-dim">{{ $row[4] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    {{-- FAQ --}}
    <section class="mx-auto max-w-3xl px-6 py-24 lg:py-32">
        <div class="reveal text-center">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">FAQ</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Pricing questions</h2>
        </div>
        <div class="mt-14 space-y-4">
            @php $faqs = [
                ['Is there a free trial?', 'Yes — start free with no credit card required, then upgrade when you\'re ready.'],
                ['Can I switch plans later?', 'Yes. Upgrade or downgrade anytime, and we prorate changes.'],
                ['Do you offer annual billing?', 'Yes — annual plans save about two months over paying monthly.'],
                ['Can I pay in Naira?', 'Yes, we accept Naira via Nomba and USD for international customers.'],
                ['What happens if I exceed my limits?', 'We\'ll notify you before you hit a limit, and you can upgrade or add usage.'],
            ]; @endphp
            @foreach ($faqs as [$q, $a])
                <div class="reveal rounded-3xl border border-ink/10 bg-white/40 p-6">
                    <h3 class="text-base font-bold text-ink">{{ $q }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $a }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-ink px-8 py-20 text-center">
            <div class="absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-lime/30 blur-[100px]"></div>
            <div class="absolute bottom-0 right-0 h-48 w-48 rounded-full bg-periwinkle/30 blur-[80px]"></div>
            <h2 class="relative font-display text-4xl font-black tracking-tight text-bone sm:text-5xl">Not sure which plan?</h2>
            <p class="relative mt-4 text-bone/70">Start free, or talk to us about a custom setup.</p>
            <div class="relative mt-8 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('login') }}" class="rounded-full bg-lime px-8 py-3.5 text-sm font-bold text-ink transition hover:bg-citron">Start free</a>
                <a href="{{ route('frontpage.contact') }}" class="rounded-full border border-bone/30 px-8 py-3.5 text-sm font-bold text-bone transition hover:border-bone/60">Contact sales</a>
            </div>
        </div>
    </section>
@endsection
