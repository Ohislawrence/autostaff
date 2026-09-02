@extends('frontpage.layouts.app')

@section('title', 'Plugin Marketplace — Nomdal')

@section('content')
    <section class="mx-auto max-w-5xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Plugins</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">Install Nomdal on your own apps</h1>
        <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-dim">
            Connect WordPress and other apps to your AI employees. Browse the marketplace, then sign in to download and install.
        </p>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($plugins as $plugin)
                <div class="rounded-2xl border border-white/10 bg-surface/50 p-7 transition hover:border-violet-500/40 hover:shadow-glow">
                    <div class="flex items-center gap-3">
                        <div class="text-3xl">{{ $plugin['icon'] ?? '🧩' }}</div>
                        <div>
                            <h3 class="text-lg font-semibold text-ink">{{ $plugin['name'] }}</h3>
                            <p class="text-xs font-mono text-ink-faint">{{ $plugin['target_platform'] }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-ink-dim">{{ $plugin['short_description'] }}</p>
                    <div class="mt-4 flex items-center gap-3 text-xs text-ink-faint">
                        @if ($plugin['current_version'])
                            <span class="rounded-full bg-violet-500/10 px-2 py-0.5 text-violet-400">v{{ $plugin['current_version'] }}</span>
                        @endif
                        <span>{{ $plugin['downloads_count'] }} downloads</span>
                    </div>
                    <a href="{{ route('login') }}" class="mt-5 inline-block rounded-full bg-gradient-to-r from-violet-500 to-cyan-500 px-4 py-2 text-sm font-semibold text-void transition hover:shadow-glow">Sign in to download</a>
                </div>
            @empty
                <p class="col-span-full text-sm text-ink-dim">No plugins are available yet.</p>
            @endforelse
        </div>
    </section>
@endsection
