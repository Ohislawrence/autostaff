@extends('frontpage.layouts.app')

@section('title', 'Product — Nomdal')

@section('content')
    <section class="mx-auto max-w-4xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Product</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">One platform for your AI workforce</h1>
        <p class="mt-6 text-lg leading-relaxed text-ink-dim">
            Nomdal gives you the tools to create, connect, and automate AI employees. Configure their role and knowledge, connect your channels, and watch them handle conversations, bookings, and orders.
        </p>

        <div class="mt-12 grid gap-6 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-7">
                <h3 class="text-lg font-semibold text-ink">Configure</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-dim">Define roles, personality, and tools, and give each AI employee the knowledge it needs.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-7">
                <h3 class="text-lg font-semibold text-ink">Connect</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-dim">Plug into WhatsApp, web chat, email, Shopify, and WooCommerce.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-7">
                <h3 class="text-lg font-semibold text-ink">Automate</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-dim">Trigger AI actions from new leads, orders, and other business events.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-7">
                <h3 class="text-lg font-semibold text-ink">Measure</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-dim">Track conversations, resolution rates, and business impact in one dashboard.</p>
            </div>
        </div>
    </section>
@endsection
