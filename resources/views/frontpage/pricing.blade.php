@extends('frontpage.layouts.app')

@section('title', 'Pricing — Nomdal')

@section('content')
    <section class="mx-auto max-w-5xl px-6 py-24">
        <div class="text-center">
            <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Pricing</p>
            <h1 class="mt-4 font-display text-4xl font-bold text-ink">Simple, transparent pricing</h1>
            <p class="mt-4 text-ink-dim">Start free. Upgrade as your AI workforce grows.</p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @php $plans = [
                ['Starter', '$0', 'For trying it out', ['1 AI employee', 'Web chat', '100 conversations / month'], false],
                ['Pro', '$49', 'For growing teams', ['5 AI employees', 'WhatsApp, web & email', 'Unlimited conversations', 'Automations & integrations'], true],
                ['Enterprise', 'Custom', 'For serious scale', ['Unlimited AI employees', 'All channels & stores', 'Custom models', 'Dedicated support'], false],
            ]; @endphp

            @foreach ($plans as [$name, $price, $tagline, $features, $featured])
                <div class="relative rounded-2xl border {{ $featured ? 'border-violet-500/50 shadow-glow' : 'border-white/10' }} bg-surface/50 p-7">
                    @if ($featured)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-violet-500 to-cyan-500 px-3 py-1 text-xs font-semibold text-void">Most popular</span>
                    @endif
                    <h3 class="text-lg font-semibold text-ink">{{ $name }}</h3>
                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="font-display text-4xl font-bold text-ink">{{ $price }}</span>
                    </div>
                    <p class="mt-2 text-sm text-ink-faint">{{ $tagline }}</p>
                    <ul class="mt-6 space-y-3 text-sm text-ink-dim">
                        @foreach ($features as $feature)
                            <li class="flex items-start gap-2"><span class="mt-0.5 text-cyan-400">✓</span> {{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('login') }}" class="mt-8 block rounded-full {{ $featured ? 'bg-gradient-to-r from-violet-500 to-cyan-500 text-void shadow-glow' : 'border border-white/10 text-ink' }} px-5 py-2.5 text-center text-sm font-semibold transition hover:shadow-glow-cyan">Get started</a>
                </div>
            @endforeach
        </div>
    </section>
@endsection
