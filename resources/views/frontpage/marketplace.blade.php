@extends('frontpage.layouts.app')

@section('title', 'Plugin Marketplace — Nomdal')
@section('meta_description', 'Install Nomdal plugins for WordPress, WooCommerce, and more. Browse the marketplace and connect your AI employees to every app.')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative mx-auto max-w-7xl px-6 pb-16 pt-16 lg:pt-24">
            <div class="reveal max-w-4xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">Plugin Marketplace</p>
                <h1 class="mt-4 font-display text-4xl font-black leading-[1.02] tracking-tight text-ink sm:text-6xl lg:text-7xl">
                    Extend Nomdal to <span class="text-periwinkle">every app you run</span>
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-dim sm:text-xl">
                    Install plugins that connect your AI employees to WordPress, WooCommerce, and other tools. Browse, sign in, and go live in minutes.
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-6">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-ink px-7 py-3.5 text-sm font-bold text-bone transition hover:bg-forest">Sign in to install</a>
                    <a href="#plugins" class="link-underline text-sm font-bold text-ink">Browse plugins ↓</a>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="grid gap-6 md:grid-cols-3">
                @php $steps = [
                    ['01', 'Browse', 'Find the plugin for your stack.'],
                    ['02', 'Sign in', 'Create a free account to download.'],
                    ['03', 'Install & connect', 'Install, connect, and sync in minutes.'],
                ]; @endphp
                @foreach ($steps as [$num, $title, $description])
                    <div class="reveal flex items-start gap-4">
                        <span class="font-display text-3xl font-black text-periwinkle">{{ $num }}</span>
                        <div>
                            <h3 class="text-base font-bold text-ink">{{ $title }}</h3>
                            <p class="mt-1 text-sm text-ink-dim">{{ $description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Plugin grid --}}
    <section id="plugins" class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-rust">Browse</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">All plugins</h2>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($plugins as $plugin)
                <div class="reveal flex flex-col rounded-3xl border border-ink/10 bg-white/40 p-8 transition hover:-translate-y-1 hover:border-ink/25">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-ltbeige text-2xl">{{ $plugin['icon'] ?? '🧩' }}</div>
                        <div>
                            <h3 class="text-lg font-bold text-ink">{{ $plugin['name'] }}</h3>
                            <p class="text-xs font-mono text-ink-faint">{{ $plugin['target_platform'] }}</p>
                        </div>
                    </div>
                    <p class="mt-4 flex-1 text-sm leading-relaxed text-ink-dim">{{ $plugin['short_description'] }}</p>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-ink-faint">
                        @if ($plugin['category'])
                            <span class="rounded-full bg-purple/60 px-2 py-0.5 font-medium text-ink-dim">{{ $plugin['category'] }}</span>
                        @endif
                        @if ($plugin['current_version'])
                            <span class="rounded-full bg-lime/20 px-2 py-0.5 font-medium text-forest">v{{ $plugin['current_version'] }}</span>
                        @endif
                        <span>{{ $plugin['downloads_count'] }} downloads</span>
                    </div>
                    <a href="{{ route('login') }}" class="mt-5 inline-block rounded-full bg-ink px-4 py-2 text-center text-sm font-bold text-bone transition hover:bg-forest">Sign in to install</a>
                </div>
            @empty
                <p class="col-span-full rounded-3xl border border-dashed border-ink/20 bg-white/40 p-12 text-center text-sm text-ink-dim">No plugins are available yet — check back soon.</p>
            @endforelse
        </div>
    </section>
    {{-- For developers --}}
    <section class="border-t border-ink/10 bg-purple/40">
        <div class="mx-auto max-w-6xl px-6 py-24 lg:py-32">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div class="reveal">
                    <p class="font-mono text-xs uppercase tracking-widest text-forest">For developers</p>
                    <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Build & publish your own</h2>
                    <p class="mt-4 text-ink-dim">Distribute integrations on a documented REST API with scoped access, versioning, and signed installs. Reach every Nomdal workspace from one listing.</p>
                    <a href="{{ route('login') }}" class="mt-6 inline-block rounded-full bg-ink px-6 py-3 text-sm font-bold text-bone transition hover:bg-forest">Start building</a>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @php $dev = [
                        ['🔌', 'REST API', 'A documented v1 API with scoped keys.'],
                        ['🧩', 'Versioning', 'Publish, deprecate, and manage versions.'],
                        ['✍️', 'Signed installs', 'Installations signed for safe distribution.'],
                        ['📈', 'Reach', 'List once, reach every workspace.'],
                    ]; @endphp
                    @foreach ($dev as [$icon, $title, $description])
                        <div class="reveal rounded-3xl border border-ink/10 bg-white/40 p-6">
                            <div class="text-2xl">{{ $icon }}</div>
                            <h3 class="mt-3 text-base font-bold text-ink">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-ink px-8 py-20 text-center">
            <div class="absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-lime/30 blur-[100px]"></div>
            <div class="absolute bottom-0 right-0 h-48 w-48 rounded-full bg-periwinkle/30 blur-[80px]"></div>
            <h2 class="relative font-display text-4xl font-black tracking-tight text-bone sm:text-5xl">Connect your stack today</h2>
            <p class="relative mt-4 text-bone/70">Start free — no credit card required.</p>
            <a href="{{ route('login') }}" class="relative mt-8 inline-block rounded-full bg-lime px-8 py-3.5 text-sm font-bold text-ink transition hover:bg-citron">Start free</a>
        </div>
    </section>
@endsection
