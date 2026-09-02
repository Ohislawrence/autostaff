@extends('frontpage.layouts.app')

@section('title', 'Integrations — Nomdal')

@section('content')
    <section class="mx-auto max-w-4xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Integrations</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">Works where your customers are</h1>
        <p class="mt-6 text-lg leading-relaxed text-ink-dim">
            Connect the channels and tools you already use, and Nomdal plugs right in.
        </p>

        <div class="mt-12 grid gap-6 sm:grid-cols-2">
            @php $integrations = [
                ['💬', 'WhatsApp', 'Reply to customers in the world\'s most popular messaging app.'],
                ['🌐', 'Web Chat', 'Embed a live AI assistant on your website.'],
                ['✉️', 'Email', 'Handle inboxes and follow-ups automatically.'],
                ['🛍️', 'Shopify', 'Sync products and create orders from conversations.'],
                ['🛒', 'WooCommerce', 'Two-way product and order sync for WordPress stores.'],
                ['📅', 'Calendar', 'Book appointments with real availability and time zones.'],
            ]; @endphp

            @foreach ($integrations as [$icon, $title, $description])
                <div class="flex items-start gap-4 rounded-2xl border border-white/10 bg-surface/50 p-7">
                    <div class="text-3xl">{{ $icon }}</div>
                    <div>
                        <h3 class="text-lg font-semibold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
