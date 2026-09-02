@extends('frontpage.layouts.app')

@section('title', 'Nomdal — AI Employees for Your Business')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-6 py-16 lg:grid-cols-2 lg:py-24">
            <div class="animate-rise">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-surface/60 px-3 py-1 text-xs font-medium text-ink-dim">
                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                    AI employees for modern teams
                </span>

                <h1 class="mt-6 font-display text-4xl font-bold leading-[1.05] tracking-tight text-ink sm:text-5xl lg:text-6xl">
                    AI employees that work your
                    <span class="bg-gradient-to-r from-violet-400 to-cyan-400 bg-clip-text text-transparent">front desk, sales, and support</span>
                    — 24/7.
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-dim">
                    Nomdal builds AI employees that handle conversations, orders, appointments, and follow-ups — built for businesses in Nigeria, and everywhere your customers are.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a href="{{ route('login') }}" class="rounded-full bg-gradient-to-r from-violet-500 to-cyan-500 px-6 py-3 text-sm font-semibold text-void shadow-glow transition hover:shadow-glow-cyan">Start free</a>
                    <a href="{{ route('frontpage.product') }}" class="rounded-full border border-white/10 bg-surface/60 px-6 py-3 text-sm font-semibold text-ink transition hover:border-white/20">See how it works</a>
                </div>

                <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-ink-faint">
                    <span>No credit card required</span>
                    <span class="text-violet-500">·</span>
                    <span>Set up in minutes</span>
                    <span class="text-violet-500">·</span>
                    <span>WhatsApp, web &amp; email</span>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-8 rounded-[2rem] bg-gradient-to-tr from-violet-600/20 to-cyan-500/10 blur-2xl"></div>
                <img src="{{ asset('images/hero-dashboard.svg') }}" alt="Nomdal AI employee conversation dashboard" class="relative w-full rounded-3xl border border-white/10 shadow-glow" />
            </div>
        </div>
    </section>

    {{-- Capability ticker --}}
    <section class="overflow-hidden border-y border-white/10 bg-surface/40 py-4">
        <div class="flex whitespace-nowrap ticker-track">
            @foreach (['Conversations', 'Appointments', 'Orders', 'Leads', 'WhatsApp', 'Shopify', 'WooCommerce', 'Follow-ups', 'Analytics', 'Automations'] as $item)
                <span class="mx-6 font-mono text-sm text-ink-faint">{{ $item }} <span class="text-violet-500">✦</span></span>
            @endforeach
            @foreach (['Conversations', 'Appointments', 'Orders', 'Leads', 'WhatsApp', 'Shopify', 'WooCommerce', 'Follow-ups', 'Analytics', 'Automations'] as $item)
                <span class="mx-6 font-mono text-sm text-ink-faint">{{ $item }} <span class="text-violet-500">✦</span></span>
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-7xl px-6 py-24">
        <div class="max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-wider text-violet-400">What Nomdal does</p>
            <h2 class="mt-4 font-display text-3xl font-bold text-ink sm:text-4xl">Everything your team does, on autopilot</h2>
            <p class="mt-4 text-ink-dim">Hire AI employees that plug into your channels and tools, then let automations handle the busy work.</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php $features = [
                ['🤖', 'AI Employees', 'Configure roles, personality, knowledge, and tools for every AI teammate.'],
                ['💬', 'Conversations', 'Answer questions and resolve issues on WhatsApp, web chat, and email — 24/7.'],
                ['📅', 'Appointments', 'Schedule, reschedule, and confirm bookings with real availability and conflict checks.'],
                ['🛒', 'Sales & Orders', 'Capture leads and create orders from WooCommerce and Shopify — automatically.'],
                ['⚡', 'Automations', 'Trigger AI actions from new leads, orders, and other business events.'],
                ['📈', 'Analytics', 'Measure conversations, resolution rates, and the impact on your business.'],
            ]; @endphp

            @foreach ($features as [$icon, $title, $description])
                <div class="group rounded-2xl border border-white/10 bg-surface/50 p-7 transition hover:border-violet-500/40 hover:shadow-glow">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500/20 to-cyan-500/10 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-lg font-semibold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-t border-white/10 bg-surface/30">
        <div class="mx-auto max-w-7xl px-6 py-24">
            <p class="font-mono text-xs uppercase tracking-wider text-cyan-400">How it works</p>
            <h2 class="mt-4 font-display text-3xl font-bold text-ink sm:text-4xl">From zero to live in three steps</h2>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @php $steps = [
                    ['01', 'Configure', 'Create an AI employee with a role, personality, and the knowledge it needs.'],
                    ['02', 'Connect', 'Plug in WhatsApp, email, Shopify, and WooCommerce so it works where your customers are.'],
                    ['03', 'Automate', 'Set triggers and let it handle conversations, bookings, and orders around the clock.'],
                ]; @endphp

                @foreach ($steps as [$num, $title, $description])
                    <div class="relative rounded-2xl border border-white/10 bg-surface/50 p-7">
                        <span class="font-mono text-sm font-semibold text-violet-400">{{ $num }}</span>
                        <h3 class="mt-3 text-lg font-semibold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-6 py-24">
        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-surface/60 px-8 py-16 text-center">
            <div class="absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-violet-600/20 blur-[100px]"></div>
            <h2 class="relative font-display text-3xl font-bold text-ink sm:text-4xl">Ready to hire your first AI employee?</h2>
            <p class="relative mt-4 text-ink-dim">Start free — no credit card required.</p>
            <a href="{{ route('login') }}" class="relative mt-8 inline-block rounded-full bg-gradient-to-r from-violet-500 to-cyan-500 px-8 py-3 text-sm font-semibold text-void shadow-glow transition hover:shadow-glow-cyan">Start free</a>
        </div>
    </section>
@endsection
