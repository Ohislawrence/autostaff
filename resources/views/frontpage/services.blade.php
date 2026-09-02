@extends('frontpage.layouts.app')

@section('title', 'Services — Nomdal')
@section('meta_description', 'Nomdal services — AI employees, automations, store integrations, appointments, and analytics for your business.')

@section('content')
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Services</p>
            <h1 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Our Services</h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-dim">Everything you need to hire, train, and manage AI employees for your business.</p>
        </div>

        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @php $services = [
                ['🤖', 'AI Employees', 'Configure roles, personality, knowledge, and tools for each AI teammate.', 'bg-peach/40'],
                ['🔁', 'Automations', 'Trigger AI actions from new leads, orders, and other business events.', 'bg-citron/40'],
                ['🛍️', 'Store Integrations', 'Sync products and orders with WooCommerce and Shopify.', 'bg-blush/40'],
                ['📅', 'Appointments', 'Automated scheduling with real availability, time zones, and conflict checks.', 'bg-periwinkle/30'],
                ['📈', 'Analytics', 'Measure conversations, resolution rates, and business impact.', 'bg-purple/70'],
                ['🔐', 'Secure by Design', 'Encrypted credentials and tenant isolation keep your data safe.', 'bg-ltbeige'],
            ]; @endphp

            @foreach ($services as [$icon, $title, $description, $tint])
                <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8 transition hover:-translate-y-1 hover:border-ink/25">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
