@extends('frontpage.layouts.app')

@section('title', 'Services — Nomdal')

@section('content')
    <section class="mx-auto max-w-7xl px-6 py-24">
        <div class="max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Services</p>
            <h1 class="mt-4 font-display text-4xl font-bold text-ink">Our Services</h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-dim">Everything you need to hire, train, and manage AI employees for your business.</p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @php $services = [
                ['🤖', 'AI Employees', 'Configure roles, personality, knowledge, and tools for each AI teammate.'],
                ['🔁', 'Automations', 'Trigger AI actions from new leads, orders, and other business events.'],
                ['🛍️', 'Store Integrations', 'Sync products and orders with WooCommerce and Shopify.'],
                ['📅', 'Appointments', 'Automated scheduling with real availability, time zones, and conflict checks.'],
                ['📈', 'Analytics', 'Measure conversations, resolution rates, and business impact.'],
                ['🔐', 'Secure by Design', 'Encrypted credentials and tenant isolation keep your data safe.'],
            ]; @endphp

            @foreach ($services as [$icon, $title, $description])
                <div class="rounded-2xl border border-white/10 bg-surface/50 p-8 transition hover:border-violet-500/40 hover:shadow-glow">
                    <div class="text-3xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-lg font-semibold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
