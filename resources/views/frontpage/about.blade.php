@extends('frontpage.layouts.app')

@section('title', 'About — Nomdal')

@section('content')
    <section class="mx-auto max-w-4xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">About</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">About Nomdal</h1>
        <p class="mt-6 text-lg leading-relaxed text-ink-dim">
            Nomdal exists to give every business an on-demand team of AI employees. We believe routine work — answering questions, booking meetings, and processing orders — should be handled automatically, so your human team can focus on what matters most.
        </p>

        <div class="mt-12 grid gap-6 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-6">
                <h3 class="text-base font-semibold text-ink">Our Mission</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-dim">Make world-class AI staffing accessible to businesses of every size.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-6">
                <h3 class="text-base font-semibold text-ink">Our Vision</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-dim">A future where every organization runs with a blend of human and AI teammates.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-6">
                <h3 class="text-base font-semibold text-ink">Our Values</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-dim">Reliability, transparency, and putting the customer experience first.</p>
            </div>
        </div>
    </section>
@endsection
