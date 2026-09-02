@extends('frontpage.layouts.app')

@section('title', 'Nomdal — AI Employees for Your Business')
@section('meta_description', 'Nomdal builds AI employees that handle conversations, orders, appointments, and follow-ups — 24/7 on WhatsApp, web, and email. Start free.')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        {{-- Animated background --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/3">
                <div class="h-[44rem] w-[44rem] rounded-full opacity-40 blur-3xl animate-spin-slow" style="background: conic-gradient(from 0deg, #ffc4ac, #caeb65, #899bff, #f5b2bd, #ffc4ac);"></div>
            </div>
            <div class="absolute -left-24 top-24 h-72 w-72 rounded-full bg-peach/50 blur-[100px] animate-drift"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-periwinkle/30 blur-[110px] animate-drift-slow"></div>
            <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-citron/40 blur-[90px] animate-drift"></div>
            <div class="absolute inset-0 opacity-[0.5]" style="background-image: radial-gradient(rgba(26,26,26,0.08) 1px, transparent 1px); background-size: 32px 32px;"></div>
            <div class="absolute left-[8%] top-1/3 h-4 w-4 rounded-full bg-lime/60 animate-float" style="animation-delay: 0.5s"></div>
            <div class="absolute right-[10%] top-1/4 h-3 w-3 rounded-full bg-periwinkle animate-float" style="animation-delay: 1.2s"></div>
            <div class="absolute bottom-1/4 left-[16%] h-5 w-5 rounded-full border-2 border-rust/40 animate-float" style="animation-delay: 2s"></div>
            <div class="absolute bottom-1/3 right-[22%] h-2.5 w-2.5 rounded-full bg-blush animate-float" style="animation-delay: 0.8s"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-6 pb-20 pt-16 lg:pt-24">
            

            <h1 class="reveal mt-8 max-w-5xl font-display text-5xl font-black leading-[0.98] tracking-tight text-ink sm:text-7xl lg:text-8xl" data-reveal-delay="60">
                AI employees that work your
                <span class="text-periwinkle">front desk</span>,
                <span class="text-lime">sales</span>, and
                <span class="text-rust">support</span>
                — 24/7.
            </h1>

            <p class="reveal mt-8 max-w-2xl text-lg leading-relaxed text-ink-dim sm:text-xl" data-reveal-delay="120">
                Nomdal builds AI employees that handle conversations, orders, appointments, and follow-ups — built for businesses in Nigeria, and everywhere your customers are.
            </p>

            <div class="reveal mt-10 flex flex-wrap items-center gap-6" data-reveal-delay="180">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-ink px-7 py-3.5 text-sm font-bold text-bone transition hover:bg-forest">Start free</a>
                <a href="{{ route('frontpage.product') }}" class="link-underline text-sm font-bold text-ink">See how it works →</a>
            </div>

            <div class="reveal mt-12 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm font-medium text-ink-faint" data-reveal-delay="240">
                <span>No credit card required</span>
                <span class="h-1 w-1 rounded-full bg-ink/30"></span>
                <span>Set up in minutes</span>
                <span class="h-1 w-1 rounded-full bg-ink/30"></span>
                <span>WhatsApp, web &amp; email</span>
            </div>
        </div>
    </section>

    {{-- Capability ticker --}}
    <section class="overflow-hidden border-y border-ink/10 bg-purple/50 py-5">
        <div class="flex whitespace-nowrap ticker-track">
            @foreach (['Conversations', 'Appointments', 'Orders', 'Leads', 'WhatsApp', 'Shopify', 'WooCommerce', 'Follow-ups', 'Analytics', 'Automations'] as $item)
                <span class="mx-8 font-display text-2xl font-black uppercase tracking-tight text-ink">{{ $item }} <span class="text-lime">●</span></span>
            @endforeach
            @foreach (['Conversations', 'Appointments', 'Orders', 'Leads', 'WhatsApp', 'Shopify', 'WooCommerce', 'Follow-ups', 'Analytics', 'Automations'] as $item)
                <span class="mx-8 font-display text-2xl font-black uppercase tracking-tight text-ink">{{ $item }} <span class="text-lime">●</span></span>
            @endforeach
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-b border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                @php $stats = [
                    ['13+', 'Built-in tools'],
                    ['3+', 'Channels (WhatsApp, web, email)'],
                    ['24/7', 'Always on duty'],
                    ['$29', 'Starting price'],
                ]; @endphp
                @foreach ($stats as [$num, $label])
                    <div class="reveal text-center">
                        <p class="font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">{{ $num }}</p>
                        <p class="mt-2 text-sm font-medium text-ink-faint">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">What Nomdal does</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Everything your team does, on autopilot</h2>
            <p class="mt-4 text-ink-dim">Hire AI employees that plug into your channels and tools, then let automations handle the busy work.</p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php $features = [
                ['🤖', 'AI Employees', 'Configure roles, personality, knowledge, and tools for every AI teammate.', 'bg-peach/40'],
                ['💬', 'Conversations', 'Answer questions and resolve issues on WhatsApp, web chat, and email — 24/7.', 'bg-citron/40'],
                ['📅', 'Appointments', 'Schedule, reschedule, and confirm bookings with real availability and conflict checks.', 'bg-blush/40'],
                ['🛒', 'Sales & Orders', 'Capture leads and create orders from WooCommerce and Shopify — automatically.', 'bg-periwinkle/30'],
                ['⚡', 'Automations', 'Trigger AI actions from new leads, orders, and other business events.', 'bg-purple/70'],
                ['📈', 'Analytics', 'Measure conversations, resolution rates, and the impact on your business.', 'bg-ltbeige'],
            ]; @endphp

            @foreach ($features as [$icon, $title, $description, $tint])
                <div class="reveal group rounded-3xl border border-ink/10 {{ $tint }} p-8 transition hover:-translate-y-1 hover:border-ink/25">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Proof pillars --}}
    <section class="border-t border-ink/10 bg-purple/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-3xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">Why Nomdal</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Does the work, not just chat</h2>
            </div>
            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @php $pillars = [
                    ['01', 'Does the work', 'Creates leads and orders, books appointments, and sends invoices & follow-ups — 13+ tools, not canned replies.', 'bg-peach/40'],
                    ['02', 'Lives where customers are', 'WhatsApp, web chat, email, REST API, and MCP tools like Gmail, Calendar, and CRM.', 'bg-citron/40'],
                    ['03', 'Enterprise trust, SMB price', 'Tenant isolation, role-based access, audit logs, and prompt-injection defense — from $29/mo.', 'bg-blush/40'],
                ]; @endphp
                @foreach ($pillars as [$num, $title, $description, $tint])
                    <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8">
                        <span class="font-display text-5xl font-black text-ink/15">{{ $num }}</span>
                        <h3 class="mt-4 text-xl font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Roles --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal max-w-2xl">
            <p class="font-mono text-xs uppercase tracking-widest text-rust">AI employees</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Hire a team, not a tool</h2>
        </div>
        <div class="mt-14 grid gap-6 md:grid-cols-3">
            @php $roles = [
                ['🤖', 'Sales Employee', 'Generates and qualifies leads, answers product questions, and creates orders.', 'bg-peach/40'],
                ['🎧', 'Support Employee', 'Resolves questions, checks order status, and creates tickets.', 'bg-citron/40'],
                ['📞', 'Receptionist', 'Schedules appointments and handles every inquiry — even after hours.', 'bg-blush/40'],
            ]; @endphp
            @foreach ($roles as [$icon, $title, $description, $tint])
                <div class="reveal rounded-3xl border border-ink/10 {{ $tint }} p-8 transition hover:-translate-y-1 hover:border-ink/25">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/60 text-2xl">{{ $icon }}</div>
                    <h3 class="mt-5 text-xl font-bold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-rust">How it works</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">From zero to live in three steps</h2>
            </div>

            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @php $steps = [
                    ['01', 'Configure', 'Create an AI employee with a role, personality, and the knowledge it needs.'],
                    ['02', 'Connect', 'Plug in WhatsApp, email, Shopify, and WooCommerce so it works where your customers are.'],
                    ['03', 'Automate', 'Set triggers and let it handle conversations, bookings, and orders around the clock.'],
                ]; @endphp

                @foreach ($steps as [$num, $title, $description])
                    <div class="reveal relative rounded-3xl border border-ink/10 bg-bone p-8">
                        <span class="font-display text-5xl font-black text-periwinkle">{{ $num }}</span>
                        <h3 class="mt-4 text-xl font-bold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Comparison --}}
    <section class="border-t border-ink/10 bg-white/40">
        <div class="mx-auto max-w-6xl px-6 py-24 lg:py-32">
            <div class="reveal max-w-2xl">
                <p class="font-mono text-xs uppercase tracking-widest text-forest">The difference</p>
                <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">A chatbot answers. An AI employee works.</h2>
            </div>
            <div class="mt-14 grid gap-6 md:grid-cols-2">
                <div class="reveal rounded-3xl border border-ink/10 bg-ltbeige p-8">
                    <h3 class="text-lg font-bold text-ink-faint">Typical chatbot</h3>
                    <ul class="mt-4 space-y-3 text-sm text-ink-dim">
                        <li>Replies with canned answers</li>
                        <li>Can't take orders or book appointments</li>
                        <li>No memory of your customers</li>
                        <li>Lives only on your website</li>
                    </ul>
                </div>
                <div class="reveal rounded-3xl border border-ink bg-ink p-8" data-reveal-delay="100">
                    <h3 class="text-lg font-bold text-lime">Nomdal AI employee</h3>
                    <ul class="mt-4 space-y-3 text-sm text-bone/85">
                        <li>Creates leads, orders, and invoices</li>
                        <li>Books and confirms appointments</li>
                        <li>Remembers customers in a built-in CRM</li>
                        <li>Works on WhatsApp, web, email &amp; API</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="mx-auto max-w-3xl px-6 py-24 lg:py-32">
        <div class="reveal text-center">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">FAQ</p>
            <h2 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Questions, answered</h2>
        </div>
        <div class="mt-14 space-y-4">
            @php $faqs = [
                ['Will it hallucinate or embarrass us?', 'Multi-layered prompt-injection defenses, human confirmation, and audit logs keep it safe — and you can transfer to a human anytime.'],
                ['Can it handle our products and booking rules?', 'Yes. Upload your docs (PDF, DOCX, CSV, URLs) and it retrieves answers with semantic search. Configure tools for your exact workflow.'],
                ['We\'re on WhatsApp, not a website.', 'That\'s the point — WhatsApp is native, plus email and web chat for when you grow.'],
                ['Is it technical to set up?', 'No-code setup with AI templates and an onboarding wizard. Most teams go live in minutes.'],
                ['Is it affordable?', 'One Business plan ($99) costs about one agent\'s salary but works 24/7 across three channels.'],
            ]; @endphp
            @foreach ($faqs as [$q, $a])
                <div class="reveal rounded-3xl border border-ink/10 bg-white/40 p-6">
                    <h3 class="text-base font-bold text-ink">{{ $q }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-dim">{{ $a }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-6 py-24 lg:py-32">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-ink px-8 py-20 text-center">
            <div class="absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-lime/30 blur-[100px]"></div>
            <div class="absolute bottom-0 right-0 h-48 w-48 rounded-full bg-periwinkle/30 blur-[80px]"></div>
            <h2 class="relative font-display text-4xl font-black tracking-tight text-bone sm:text-5xl">Ready to hire your first AI employee?</h2>
            <p class="relative mt-4 text-bone/70">Start free — no credit card required.</p>
            <a href="{{ route('login') }}" class="relative mt-8 inline-block rounded-full bg-lime px-8 py-3.5 text-sm font-bold text-ink transition hover:bg-citron">Start free</a>
        </div>
    </section>
@endsection
