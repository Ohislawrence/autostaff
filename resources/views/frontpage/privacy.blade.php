@extends('frontpage.layouts.app')

@section('title', 'Privacy Policy — Nomdal')

@section('content')
    <section class="mx-auto max-w-3xl px-6 py-24">
        <p class="font-mono text-xs uppercase tracking-wider text-violet-400">Legal</p>
        <h1 class="mt-4 font-display text-4xl font-bold text-ink">Privacy Policy</h1>
        <div class="mt-8 space-y-6 text-ink-dim leading-relaxed">
            <p>Your privacy matters to us. This policy explains what data Nomdal collects, how it is used, and the choices you have.</p>
            <h2 class="text-lg font-semibold text-ink">1. Data we collect</h2>
            <p>We collect the information you provide when you create an account, configure AI employees, and connect integrations — including organization details, conversations, and store data needed to operate the service.</p>
            <h2 class="text-lg font-semibold text-ink">2. How we use it</h2>
            <p>We use your data to provide and improve the service, process AI conversations, and deliver the features you enable. We do not sell your data.</p>
            <h2 class="text-lg font-semibold text-ink">3. Data security</h2>
            <p>Integration credentials are encrypted and tenant data is isolated between organizations. Access is restricted to authorized users.</p>
            <h2 class="text-lg font-semibold text-ink">4. Contact</h2>
            <p>Questions? Reach us at <a href="mailto:hello@nomdal.example" class="text-cyan-400 hover:text-cyan-500">hello@nomdal.example</a>.</p>
        </div>
    </section>
@endsection
