@extends('frontpage.layouts.app')

@section('title', 'Integrations — Nomdal')
@section('meta_description', 'Connect Nomdal to WhatsApp, web chat, email, Shopify, WooCommerce, and Calendar. Works where your customers are.')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Integrations</p>
            <h1 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Works where your customers are</h1>
            <p class="mt-6 text-lg leading-relaxed text-ink-dim">
                Connect the channels and tools you already use, and Nomdal plugs right in.
            </p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2">
            @php $integrations = [
                ['💬', 'WhatsApp', 'Reply to customers in the world\'s most popular messaging app.', 'bg-peach/40'],
                ['🌐', 'Web Chat', 'Embed a live AI assistant on your website.', 'bg-citron/40'],
                ['✉️', 'Email', 'Handle inboxes and follow-ups automatically.', 'bg-blush/40'],
                ['🛍️', 'Shopify', 'Sync products and create orders from conversations.', 'bg-periwinkle/30'],
                ['🛒', 'WooCommerce', 'Two-way product and order sync for WordPress stores.', 'bg-purple/70'],
                ['📅', 'Calendar', 'Book appointments with real availability and time zones.', 'bg-ltbeige'],
            ]; @endphp

            @foreach ($integrations as [$icon, $title, $description, $tint])
                <div class="reveal flex items-start gap-4 rounded-3xl border border-ink/10 {{ $tint }} p-7">
                    <div class="text-3xl">{{ $icon }}</div>
                    <div>
                        <h3 class="text-lg font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
