@extends('frontpage.layouts.app')

@section('title', 'AI Employees — Nomdal')

@section('content')
    <section class="mx-auto max-w-5xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">AI Employees</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">Hire an AI employee for every job</h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-dim">
            Start from a proven template or build your own. Every AI employee comes with a role, personality, knowledge base, and the tools it needs to get work done.
        </p>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php $roles = [
                ['🎧', 'Support Agent', 'Answers questions and resolves issues across your channels.'],
                ['🗓️', 'Receptionist', 'Books, reschedules, and confirms appointments 24/7.'],
                ['🛍️', 'Sales Assistant', 'Qualifies leads and helps customers place orders.'],
                ['📞', 'Follow-up Specialist', 'Reaches out after purchases and keeps leads warm.'],
                ['📦', 'Order Desk', 'Creates and updates orders in Shopify and WooCommerce.'],
                ['🧠', 'Knowledge Base', 'Answers from your docs, files, and FAQs instantly.'],
            ]; @endphp

            @foreach ($roles as [$icon, $title, $description])
                <div class="rounded-2xl border border-white/10 bg-surface/50 p-7 transition hover:border-violet-500/40 hover:shadow-glow">
                    <div class="text-3xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-lg font-semibold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
