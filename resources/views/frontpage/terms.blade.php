@extends('frontpage.layouts.app')

@section('title', 'Terms of Service — Nomdal')
@section('meta_description', 'Nomdal terms of service — the rules for using the platform.')

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-24 lg:py-32">
        <p class="font-mono text-xs uppercase tracking-widest text-forest">Legal</p>
        <h1 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Terms of Service</h1>
        <div class="mt-8 space-y-6 leading-relaxed text-ink-dim">
            <p>By using Nomdal, you agree to these terms. Please read them carefully.</p>
            <h2 class="text-lg font-bold text-ink">1. Use of the service</h2>
            <p>You may use Nomdal to build and deploy AI employees for your business, provided you comply with applicable laws and these terms.</p>
            <h2 class="text-lg font-bold text-ink">2. Your content</h2>
            <p>You retain ownership of the content and data you provide. You grant us a limited license to process it solely to operate the service.</p>
            <h2 class="text-lg font-bold text-ink">3. Acceptable use</h2>
            <p>You agree not to use the service for unlawful, harmful, or deceptive activities.</p>
            <h2 class="text-lg font-bold text-ink">4. Contact</h2>
            <p>Questions? Reach us at <a href="mailto:hello@nomdal.com" class="font-semibold text-forest hover:underline">hello@nomdal.com</a>.</p>
        </div>
    </section>
@endsection
