@extends('frontpage.layouts.app')

@section('title', 'Docs — Nomdal')

@section('content')
    <section class="mx-auto max-w-4xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-cyan-400">Docs</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">Documentation</h1>
        <p class="mt-6 text-lg leading-relaxed text-ink-dim">
            Guides and references for building and deploying AI employees with Nomdal.
        </p>

        <div class="mt-12 space-y-4">
            @php $docs = [
                ['Getting started', 'Create your organization and deploy your first AI employee.'],
                ['Configuring AI employees', 'Roles, personality, knowledge, and tools.'],
                ['Connecting channels', 'WhatsApp, web chat, email, and store integrations.'],
                ['WordPress plugin', 'Connect your WordPress site to sync contact-form leads and WooCommerce orders.'],
                ['Building automations', 'Trigger AI actions from business events.'],
                ['Appointments & scheduling', 'Availability, services, and conflict handling.'],
            ]; @endphp

            @foreach ($docs as [$title, $description])
                <a href="{{ route('frontpage.product') }}" class="block rounded-2xl border border-white/10 bg-surface/50 p-6 transition hover:border-violet-500/40">
                    <h3 class="text-lg font-semibold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink-dim">{{ $description }}</p>
                </a>
            @endforeach
        </div>
    </section>
@endsection
