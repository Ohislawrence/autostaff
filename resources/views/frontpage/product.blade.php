@extends('frontpage.layouts.app')

@section('title', 'Product — Nomdal')
@section('meta_description', 'Explore the Nomdal platform: create AI employees with roles, knowledge bases, and 13+ tools, then deploy them on WhatsApp, web chat, email, and API.')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative mx-auto max-w-7xl px-6 pb-16 pt-16 lg:pt-24">
            <div class="reveal max-w-4xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">Product</p>
                <h1 class="mt-4 font-display text-4xl font-black leading-[1.02] tracking-tight text-ink sm:text-6xl lg:text-7xl">
                    One platform for your <span class="text-periwinkle">entire AI workforce</span>
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-dim sm:text-xl">
                    Create AI employees with a role, personality, and knowledge base — then plug them into WhatsApp, web, and email to sell, support, and schedule around the clock.
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-6">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-ink px-7 py-3.5 text-sm font-bold text-bone transition hover:bg-forest">Start free</a>
                    <a href="{{ route('frontpage.pricing') }}" class="link-underline text-sm font-bold text-ink">See pricing →</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Platform pillars --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-rust">How it works</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Configure → Connect → Automate → Measure</h2>
            </div>
            <div class="mt-14 grid gap-6 sm:grid-cols-2">
                @php $blocks = [
                    ['01', 'Configure', 'Define the role, personality, knowledge, and tools for each AI employee.', 'bg-peach/40'],
                    ['02', 'Connect', 'Plug into WhatsApp, web chat, email, Shopify, and WooCommerce.', 'bg-citron/40'],
                    ['03', 'Automate', 'Trigger AI actions from new leads, orders, and business events.', 'bg-blush/40'],
                    ['04', 'Measure', 'Track conversations, resolution rates, and business impact in one dashboard.', 'bg-periwinkle/30'],
                ]; @endphp
                @foreach ($blocks as [$num, $title, $description, $tint])
                    <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8 transition hover:-translate-y-1 hover:border-ink/25">
                        <span class="font-display text-4xl font-black text-ink/20">{{ $num }}</span>
                        <h3 class="mt-4 text-xl font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- AI Employees --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-3xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">AI employees</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Hire every role, or build your own</h2>
            <p class="mt-4 text-ink-dim">Start from a proven template and tune the role, personality, knowledge, and tools. Your AI employee is always on, always consistent, and never off the clock — and it does the work, not just the chat.</p>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php $roles = [
                ['🎯', 'Sales Employee', 'Finds prospects, qualifies leads, starts conversations, follows up, and alerts you when someone is ready to talk.', 'bg-peach/40'],
                ['🎧', 'Support Employee', 'Answers questions, resolves common issues, checks orders, and escalates problems.', 'bg-citron/40'],
                ['📞', 'Receptionist', 'Books appointments, answers enquiries, and keeps your calendar organized.', 'bg-blush/40'],
                ['📦', 'Order Employee', 'Handles product enquiries, creates orders, sends invoices, and keeps customers updated.', 'bg-periwinkle/30'],
                ['🗂️', 'Admin Employee', 'Handles repetitive requests, documents, reports, and routine business tasks.', 'bg-purple/70'],
            ]; @endphp
            @foreach ($roles as [$icon, $title, $description, $tint])
                <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>
    {{-- Knowledge + Tools --}}
    <section class="border-t border-ink/10 bg-purple/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="grid gap-12 lg:grid-cols-2">
                <div class="reveal">
                    <p class="font-mono text-xs uppercase tracking-widest text-forest">Knowledge base + RAG</p>
                    <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Answers grounded in your business</h2>
                    <p class="mt-4 text-ink-dim">Upload PDFs, DOCX, TXT, CSVs, and URLs. Nomdal retrieves the right answer with semantic search — embeddings and cosine similarity — so every reply is accurate and on-brand.</p>
                    <ul class="mt-6 space-y-3 text-sm text-ink-dim">
                        <li>· Ingest documents, spreadsheets, and web pages</li>
                        <li>· Semantic search with embeddings</li>
                        <li>· Multi-document knowledge bases per employee</li>
                    </ul>
                </div>
                <div class="reveal" data-reveal-delay="100">
                    <p class="font-mono text-xs uppercase tracking-widest text-rust">Tool framework</p>
                    <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">13+ tools that do real work</h2>
                    <p class="mt-4 text-ink-dim">AI employees don't just talk — they act. Search products, create leads and customers, place and cancel orders, book appointments, generate quotes and invoices, and transfer to a human when needed.</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach (['search_products', 'create_lead', 'create_order', 'get_order_status', 'schedule_appointment', 'create_task', 'transfer_to_human'] as $tool)
                            <span class="rounded-full border border-ink/15 bg-white/50 px-3 py-1 font-mono text-xs text-ink-dim">{{ $tool }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Channels --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Every channel</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Live where your customers are</h2>
        </div>
        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php $channels = [
                ['💬', 'WhatsApp', 'Native Cloud API for the world\'s most popular business channel.', 'bg-peach/40'],
                ['🌐', 'Web Chat', 'An embeddable widget that drops into any site in minutes.', 'bg-citron/40'],
                ['✉️', 'Email', 'Handle inboxes and follow-ups automatically.', 'bg-blush/40'],
                ['🔌', 'REST API', 'Build custom integrations on a documented v1 API.', 'bg-periwinkle/30'],
                ['🧩', 'MCP Tools', 'Connect Gmail, Calendar, CRM, and Microsoft 365.', 'bg-purple/70'],
                ['🛒', 'Commerce', 'Sync products and orders from Shopify and WooCommerce.', 'bg-ltbeige'],
            ]; @endphp
            @foreach ($channels as [$icon, $title, $description, $tint])
                <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>
    {{-- CRM, Automations, Analytics --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-rust">Operate & grow</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">A full business engine, included</h2>
            </div>
            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @php $ops = [
                    ['🎯', 'CRM & lead scoring', 'A built-in pipeline with configurable scoring and AI-generated lead attribution.', 'bg-peach/40'],
                    ['⚡', 'Automation engine', 'Trigger → condition → action workflows: 6 triggers, 10 operators, 7 actions.', 'bg-citron/40'],
                    ['📈', 'Analytics', 'A 12-KPI dashboard with conversation funnel, 6-month trends, and AI cost tracking.', 'bg-blush/40'],
                ]; @endphp
                @foreach ($ops as [$icon, $title, $description, $tint])
                    <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                        <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Security --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
            <div class="reveal">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">Security & trust</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Enterprise-grade, from $29</h2>
                <p class="mt-4 text-ink-dim">Your AI employees are safe by default. Every write is audited, every tenant is isolated, and every prompt is screened.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @php $security = [
                    ['🛡️', 'Tenant isolation', 'Global scopes keep every organization\'s data separate.'],
                    ['🧠', 'Prompt-injection defense', 'Multi-layered screening for customer-facing agents.'],
                    ['🔐', 'Encrypted credentials', 'Integrations and MCP keys are encrypted at rest.'],
                    ['📜', 'Audit logging & RBAC', '6 roles, 78 permissions, and a full audit trail.'],
                ]; @endphp
                @foreach ($security as [$icon, $title, $description])
                    <div class="reveal rounded-3xl border border-ink/10 bg-white/40 p-6">
                        <div class="text-2xl">{{ $icon }}</div>
                        <h3 class="mt-3 text-base font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
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
            <h2 class="relative font-display text-4xl font-black tracking-tight text-bone sm:text-5xl">Build your AI workforce today</h2>
            <p class="relative mt-4 text-bone/70">Start free — no credit card required.</p>
            <a href="{{ route('login') }}" class="relative mt-8 inline-block rounded-full bg-lime px-8 py-3.5 text-sm font-bold text-ink transition hover:bg-citron">Start free</a>
        </div>
    </section>
@endsection
