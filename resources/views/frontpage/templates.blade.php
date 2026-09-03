@extends('frontpage.layouts.app')

@section('title', 'AI Employees — Nomdal')
@section('meta_description', 'Browse pre-built AI employee templates — Sales, Support, Receptionist, and more — and hire your first AI teammate in minutes.')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative mx-auto max-w-7xl px-6 pb-16 pt-16 lg:pt-24">
            <div class="reveal max-w-4xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">AI Employees</p>
                <h1 class="mt-4 font-display text-4xl font-black leading-[1.02] tracking-tight text-ink sm:text-6xl lg:text-7xl">
                    Hire an AI employee for <span class="text-periwinkle">every job</span>
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-dim sm:text-xl">
                    Start from a proven template or build your own. Every AI employee comes with a role, personality, knowledge base, and the tools it needs to get work done — on your channels, around the clock.
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-6">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-ink px-7 py-3.5 text-sm font-bold text-bone transition hover:bg-forest">Start free</a>
                    <a href="{{ route('frontpage.product') }}" class="link-underline text-sm font-bold text-ink">See the platform →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Core roles --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-rust">Core roles</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Your core roster</h2>
                <p class="mt-4 text-ink-dim">Five proven employees to start from — each with the tools it needs to do real work, not just chat.</p>
            </div>
            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php $core = [
                    ['🎯', 'Sales Employee', 'Finds prospects, qualifies leads, starts conversations, follows up, and alerts you when someone is ready to talk.', ['create_lead', 'create_customer', 'search_products', 'get_price'], 'bg-peach/40'],
                    ['🎧', 'Support Employee', 'Answers questions, resolves common issues, checks orders, and escalates problems.', ['get_order_status', 'create_task', 'transfer_to_human'], 'bg-citron/40'],
                    ['📞', 'Receptionist', 'Books appointments, answers enquiries, and keeps your calendar organized.', ['schedule_appointment', 'get_available_slots', 'transfer_to_human'], 'bg-blush/40'],
                    ['📦', 'Order Employee', 'Handles product enquiries, creates orders, sends invoices, and keeps customers updated.', ['create_order', 'get_order', 'get_order_status', 'check_inventory'], 'bg-periwinkle/30'],
                    ['🗂️', 'Admin Employee', 'Handles repetitive requests, documents, reports, and routine business tasks.', ['create_task', 'create_customer', 'transfer_to_human'], 'bg-purple/70'],
                ]; @endphp
                @foreach ($core as [$icon, $title, $description, $tools, $tint])
                    <div class="reveal flex flex-col rounded-3xl border border-ink/10 {{ $tint }} p-8">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                        <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($tools as $tool)
                                <span class="rounded-full border border-ink/15 bg-white/50 px-2.5 py-1 font-mono text-[11px] text-ink-dim">{{ $tool }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    {{-- Specialty roles --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Specialty roles</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Or hire a specialist</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php $specialty = [
                ['📞', 'Follow-up Specialist', 'Reaches out after purchases and keeps leads warm.', 'bg-peach/40'],
                ['📦', 'Order Desk', 'Creates and updates orders in Shopify and WooCommerce.', 'bg-citron/40'],
                ['🧠', 'Knowledge Base', 'Answers from your docs, files, and FAQs instantly.', 'bg-blush/40'],
                ['🎯', 'Lead Qualifier', 'Scores inbound leads against your ICP before they reach sales.', 'bg-periwinkle/30'],
                ['💳', 'Collections Assistant', 'Sends quotes, invoices, and payment reminders.', 'bg-purple/70'],
                ['📣', 'Outbound SDR', 'Drafts and sends personalized outreach at scale.', 'bg-ltbeige'],
            ]; @endphp
            @foreach ($specialty as [$icon, $title, $description, $tint])
                <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8 transition hover:-translate-y-1 hover:border-ink/25">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Anatomy --}}
    <section class="border-t border-ink/10 bg-purple/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-rust">Anatomy</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">What every AI employee includes</h2>
            </div>
            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php $anatomy = [
                    ['🎭', 'Role & personality', 'Give it a name, personality, tone, and language that match your brand.', 'bg-peach/40'],
                    ['📚', 'Knowledge base', 'Attach documents and FAQs so it answers from your own content.', 'bg-citron/40'],
                    ['🧰', 'Tools', 'Enable the exact tools it can use — from search to orders to scheduling.', 'bg-blush/40'],
                    ['💬', 'Channels', 'Deploy on web chat, WhatsApp, email, or your own API.', 'bg-periwinkle/30'],
                    ['🕐', 'Working hours', 'Set availability — or keep it on 24/7.', 'bg-purple/70'],
                    ['👋', 'Escalation', 'Define rules to hand off to a human teammate when it matters.', 'bg-ltbeige'],
                ]; @endphp
                @foreach ($anatomy as [$icon, $title, $description, $tint])
                    <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                        <h3 class="mt-5 text-lg font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    {{-- Industry templates --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Built for your industry</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Start from a vertical template</h2>
            <p class="mt-4 text-ink-dim">Pre-configured employees for common industries, so you go live even faster.</p>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php $industries = [
                ['🛍️', 'E-commerce store', 'Product Q&A, order status, and checkout help.', 'bg-peach/40'],
                ['🛋️', 'Furniture & retail', 'Quotes, inventory checks, and showroom inquiries.', 'bg-citron/40'],
                ['🩺', 'Clinic & wellness', 'Appointment booking, reminders, and intake.', 'bg-blush/40'],
                ['💇', 'Salon & spa', 'Scheduling, services, and no-show follow-ups.', 'bg-periwinkle/30'],
                ['🏠', 'Real estate', 'Listing questions and viewing requests.', 'bg-purple/70'],
                ['⚖️', 'Law firm', 'Intake, consultations, and case-status updates.', 'bg-ltbeige'],
            ]; @endphp
            @foreach ($industries as [$icon, $title, $description, $tint])
                <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8 transition hover:-translate-y-1 hover:border-ink/25">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-lg font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
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
            <p class="relative mt-4 text-bone/70">Start free — no credit card required.</p>
            <a href="{{ route('login') }}" class="relative mt-8 inline-block rounded-full bg-lime px-8 py-3.5 text-sm font-bold text-ink transition hover:bg-citron">Start free</a>
        </div>
    </section>
@endsection
