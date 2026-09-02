@extends('frontpage.layouts.app')

@section('title', 'About — Nomdal')
@section('meta_description', 'Nomdal gives every business an on-demand team of AI employees. Built in Lagos, for the world.')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative mx-auto max-w-7xl px-6 pb-16 pt-16 lg:pt-24">
            <div class="reveal max-w-4xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">About Nomdal</p>
                <h1 class="mt-4 font-display text-4xl font-black leading-[1.02] tracking-tight text-ink sm:text-6xl lg:text-7xl">
                    Routine work, <span class="text-periwinkle">handled automatically</span>
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-dim sm:text-xl">
                    Nomdal exists to give every business an on-demand team of AI employees — so your human team can focus on what matters most.
                </p>
            </div>
        </div>
    </section>

    {{-- Story --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-3xl px-6 py-24 lg:py-32">
            <div class="reveal">
                <p class="font-mono text-xs uppercase tracking-widest text-rust">Our story</p>
                <h2 class="mt-4 font-display text-3xl font-black tracking-tight text-ink sm:text-4xl">Built in Lagos, for the world</h2>
            </div>
            <div class="reveal mt-8 space-y-4 text-lg leading-relaxed text-ink-dim" data-reveal-delay="100">
                <p>Businesses don't fail for lack of ambition — they get buried by the busywork: answering the same questions, chasing bookings, and processing orders at all hours.</p>
                <p>We built Nomdal to change that. We believe routine work should run itself, on the channels customers already use — WhatsApp, web, and email — so every business, from a Lagos storefront to a global store, can afford a team that never sleeps.</p>
                <p>Today Nomdal is a platform for hiring AI employees that sell, support, and schedule — grounded in your own knowledge, on your own channels, around the clock.</p>
            </div>
        </div>
    </section>

    {{-- Mission / Vision / Values --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Who we are</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Our mission, vision, and values</h2>
        </div>
        <div class="mt-14 grid gap-6 md:grid-cols-3">
            @php $values = [
                ['🎯', 'Our Mission', 'Make world-class AI staffing accessible to businesses of every size.', 'bg-peach/40'],
                ['🔭', 'Our Vision', 'A future where every organization runs with a blend of human and AI teammates.', 'bg-citron/40'],
                ['🤝', 'Our Values', 'Reliability, transparency, and putting the customer experience first.', 'bg-blush/40'],
            ]; @endphp
            @foreach ($values as [$icon, $title, $description, $tint])
                <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-lg font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>
    {{-- Principles --}}
    <section class="border-t border-ink/10 bg-purple/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-rust">What we believe</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Principles we build by</h2>
            </div>
            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php $principles = [
                    ['🛡️', 'Reliability', 'Your AI employees should be dependable, day and night.', 'bg-peach/40'],
                    ['🪟', 'Transparency', 'You should always see what your AI is doing and why.', 'bg-citron/40'],
                    ['🎧', 'Customer-first', 'Every decision starts with the people you serve.', 'bg-blush/40'],
                    ['🌍', 'Local-first', 'Built for how businesses actually work — from Lagos to everywhere.', 'bg-periwinkle/30'],
                    ['🔐', 'Security by default', 'Enterprise-grade safety, on every plan.', 'bg-purple/70'],
                    ['⚡', 'Simplicity', 'Powerful enough to do the work, simple enough to start today.', 'bg-ltbeige'],
                ]; @endphp
                @foreach ($principles as [$icon, $title, $description, $tint])
                    <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                        <h3 class="mt-5 text-lg font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
            @php $stats = [
                ['13+', 'Built-in tools'],
                ['3+', 'Channels'],
                ['24/7', 'Always on'],
                ['$29', 'Starting price'],
            ]; @endphp
            @foreach ($stats as [$num, $label])
                <div class="reveal text-center">
                    <p class="font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">{{ $num }}</p>
                    <p class="mt-2 text-sm font-medium text-ink-faint">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-ink px-8 py-20 text-center">
            <div class="absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-lime/30 blur-[100px]"></div>
            <div class="absolute bottom-0 right-0 h-48 w-48 rounded-full bg-periwinkle/30 blur-[80px]"></div>
            <h2 class="relative font-display text-4xl font-black tracking-tight text-bone sm:text-5xl">Hire your first AI employee today</h2>
            <p class="relative mt-4 text-bone/70">Join us, and put the busywork on autopilot.</p>
            <a href="{{ route('login') }}" class="relative mt-8 inline-block rounded-full bg-lime px-8 py-3.5 text-sm font-bold text-ink transition hover:bg-citron">Start free</a>
        </div>
    </section>
@endsection
