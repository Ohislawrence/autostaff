@extends('frontpage.layouts.app')

@section('title', 'Contact — Nomdal')

@section('content')
    <section class="mx-auto max-w-4xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Contact</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">Contact Us</h1>
        <p class="mt-6 text-lg leading-relaxed text-ink-dim">Have a question or ready to get started? We'd love to hear from you.</p>

        <div class="mt-12 grid gap-6 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-6">
                <div class="text-2xl">✉️</div>
                <h3 class="mt-4 text-base font-semibold text-ink">Email</h3>
                <a href="mailto:hello@nomdal.example" class="mt-1 block text-sm text-cyan-400 hover:text-cyan-500">hello@nomdal.example</a>
            </div>
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-6">
                <div class="text-2xl">📞</div>
                <h3 class="mt-4 text-base font-semibold text-ink">Phone</h3>
                <p class="mt-1 text-sm text-ink-dim">+1 (555) 000-0000</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-surface/50 p-6">
                <div class="text-2xl">📍</div>
                <h3 class="mt-4 text-base font-semibold text-ink">Office</h3>
                <p class="mt-1 text-sm text-ink-dim">Lagos, Nigeria</p>
            </div>
        </div>

        <div class="mt-12 flex">
            <a href="mailto:hello@nomdal.example" class="rounded-full bg-gradient-to-r from-violet-500 to-cyan-500 px-6 py-3 text-sm font-semibold text-void shadow-glow transition hover:shadow-glow-cyan">Send us an email</a>
        </div>
    </section>
@endsection
