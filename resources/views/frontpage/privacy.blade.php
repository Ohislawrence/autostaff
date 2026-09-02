@extends('frontpage.layouts.app')

@section('title', 'Privacy Policy — Nomdal')
@section('meta_description', 'Nomdal privacy policy — how we collect, use, and protect your data.')

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-24 lg:py-32">
        <p class="font-mono text-xs uppercase tracking-widest text-forest">Legal</p>
        <h1 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Privacy Policy</h1>
        <div class="mt-8 space-y-6 leading-relaxed text-ink-dim">
            <p>Your privacy matters to us. This policy explains what data Nomdal collects, how it is used, and the choices you have.</p>
            <h2 class="text-lg font-bold text-ink">1. Data we collect</h2>
            <p>We collect the information you provide when you create an account, configure AI employees, and connect integrations — including organization details, conversations, and store data needed to operate the service.</p>
            <h2 class="text-lg font-bold text-ink">2. How we use it</h2>
            <p>We use your data to provide and improve the service, process AI conversations, and deliver the features you enable. We do not sell your data.</p>
            <h2 class="text-lg font-bold text-ink">3. Data security</h2>
            <p>Integration credentials are encrypted and tenant data is isolated between organizations. Access is restricted to authorized users.</p>
            <h2 class="text-lg font-bold text-ink">4. Contact</h2>
            <p>Questions? Reach us at <a href="mailto:hello@nomdal.com" class="font-semibold text-forest hover:underline">hello@nomdal.com</a>.</p>
        </div>
    </section>
@endsection
