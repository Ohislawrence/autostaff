@extends('frontpage.layouts.app')

@section('title', 'Contact — Nomdal')
@section('meta_description', 'Contact Nomdal — email hello@nomdal.com or call +234 902 223 9628. Based in Lagos, Nigeria.')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative mx-auto max-w-7xl px-6 pb-16 pt-16 lg:pt-24">
            <div class="reveal max-w-3xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">Contact</p>
                <h1 class="mt-4 font-display text-4xl font-black leading-[1.02] tracking-tight text-ink sm:text-6xl lg:text-7xl">
                    Let's put your team on <span class="text-periwinkle">autopilot</span>
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-dim sm:text-xl">
                    Have a question or ready to hire your first AI employee? Reach us on any channel below — we usually reply within one business day.
                </p>
            </div>
        </div>
    </section>

    {{-- Contact methods --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php $methods = [
                    ['✉️', 'Email', 'hello@nomdal.com', 'mailto:hello@nomdal.com', 'Send us an email anytime.', 'bg-peach/40'],
                    ['📞', 'Call', '+234 902 223 9628', 'tel:+2349022239628', 'Mon–Fri, 9am–6pm WAT.', 'bg-citron/40'],
                    ['💬', 'WhatsApp', '+234 902 223 9628', 'https://wa.me/2349022239628', 'Chat with us directly.', 'bg-blush/40'],
                    ['📍', 'Office', 'Lagos, Nigeria', null, 'Built in Lagos, for the world.', 'bg-periwinkle/30'],
                ]; @endphp
                @foreach ($methods as [$icon, $title, $value, $href, $note, $tint])
                    <div class="reveal flex flex-col rounded-3xl border border-ink/10 {{ $tint }} p-8">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                        <h3 class="mt-5 text-base font-bold text-ink">{{ $title }}</h3>
                        @if ($href)
                            <a href="{{ $href }}" class="mt-1 break-all text-sm font-semibold text-forest hover:underline">{{ $value }}</a>
                        @else
                            <p class="mt-1 text-sm font-semibold text-ink-dim">{{ $value }}</p>
                        @endif
                        <p class="mt-2 text-sm text-ink-dim">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-ink px-8 py-20 text-center">
            <div class="absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-lime/30 blur-[100px]"></div>
            <div class="absolute bottom-0 right-0 h-48 w-48 rounded-full bg-periwinkle/30 blur-[80px]"></div>
            <h2 class="relative font-display text-4xl font-black tracking-tight text-bone sm:text-5xl">Prefer to just get started?</h2>
            <p class="relative mt-4 text-bone/70">Create a free account and hire your first AI employee in minutes.</p>
            <a href="{{ route('login') }}" class="relative mt-8 inline-block rounded-full bg-lime px-8 py-3.5 text-sm font-bold text-ink transition hover:bg-citron">Start free</a>
        </div>
    </section>
@endsection
