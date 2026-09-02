@extends('frontpage.layouts.app')

@section('title', 'Terms of Service — Nomdal')

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Legal</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">Terms of Service</h1>
        <div class="mt-8 space-y-6 text-ink-dim leading-relaxed">
            <p>By using Nomdal, you agree to these terms. Please read them carefully.</p>
            <h2 class="text-lg font-semibold text-ink">1. Use of the service</h2>
            <p>You may use Nomdal to build and deploy AI employees for your business, provided you comply with applicable laws and these terms.</p>
            <h2 class="text-lg font-semibold text-ink">2. Your content</h2>
            <p>You retain ownership of the content and data you provide. You grant us a limited license to process it solely to operate the service.</p>
            <h2 class="text-lg font-semibold text-ink">3. Acceptable use</h2>
            <p>You agree not to use the service for unlawful, harmful, or deceptive activities.</p>
            <h2 class="text-lg font-semibold text-ink">4. Contact</h2>
            <p>Questions? Reach us at <a href="mailto:hello@nomdal.example" class="text-cyan-400 hover:text-cyan-500">hello@nomdal.example</a>.</p>
        </div>
    </section>
@endsection
