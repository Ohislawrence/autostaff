@extends('frontpage.layouts.app')

@section('title', 'Docs — Nomdal')
@section('meta_description', 'Guides for building and deploying AI employees with Nomdal — getting started, configuring roles, connecting channels, and automations.')

@section('content')
    <section class="mx-auto max-w-7xl px-6 py-16 lg:py-24">
        <div class="reveal max-w-3xl">
            <p class="font-mono text-xs uppercase tracking-widest text-forest">Docs</p>
            <h1 class="mt-4 font-display text-4xl font-black tracking-tight text-ink sm:text-5xl">Documentation</h1>
            <p class="mt-4 text-lg leading-relaxed text-ink-dim">Guides for building and deploying AI employees with Nomdal.</p>
        </div>

        <div class="mt-12 grid gap-10 lg:grid-cols-[240px_1fr]">
            <aside class="hidden lg:block">
                <nav class="sticky top-24 space-y-1 text-sm" aria-label="Docs navigation">
                    @php $nav = [
                        ['getting-started', 'Getting started'],
                        ['configuring', 'Configuring AI employees'],
                        ['channels', 'Connecting channels'],
                        ['wordpress', 'WordPress plugin'],
                        ['automations', 'Building automations'],
                        ['appointments', 'Appointments & scheduling'],
                    ]; @endphp
                    @foreach ($nav as [$id, $label])
                        <a href="#{{ $id }}" class="block rounded-lg px-3 py-2 font-medium text-ink-dim transition hover:bg-white/50 hover:text-ink">{{ $label }}</a>
                    @endforeach
                </nav>
            </aside>

            <div class="max-w-3xl space-y-16">
                <section id="getting-started" class="scroll-mt-24">
                    <h2 class="font-display text-2xl font-black tracking-tight text-ink sm:text-3xl">Getting started</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-dim">Go from signup to a live AI employee in five steps.</p>
                    <ol class="mt-4 space-y-2 text-sm text-ink-dim">
                        <li><span class="font-semibold text-ink">1. Create an account</span> — sign up free, no credit card required.</li>
                        <li><span class="font-semibold text-ink">2. Set up your organization</span> — your workspace, and invite your team.</li>
                        <li><span class="font-semibold text-ink">3. Create an AI employee</span> — start from a template (Sales, Support, or Receptionist) or build custom.</li>
                        <li><span class="font-semibold text-ink">4. Connect a channel</span> — web chat is the fastest way to go live.</li>
                        <li><span class="font-semibold text-ink">5. Test and launch</span> — try a conversation, then publish.</li>
                    </ol>
                </section>

                <section id="configuring" class="scroll-mt-24">
                    <h2 class="font-display text-2xl font-black tracking-tight text-ink sm:text-3xl">Configuring AI employees</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-dim">Every AI employee is defined by a few key settings:</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-dim">
                        <li><span class="font-semibold text-ink">Role</span> — the job it does: sales, support, receptionist, or custom.</li>
                        <li><span class="font-semibold text-ink">Personality &amp; tone</span> — how it speaks, and in which language.</li>
                        <li><span class="font-semibold text-ink">Knowledge base</span> — attach documents (PDF, DOCX, TXT, CSV) and URLs so it answers from your content.</li>
                        <li><span class="font-semibold text-ink">Tools</span> — enable what it can do: search products, create orders, book appointments, or transfer to a human.</li>
                        <li><span class="font-semibold text-ink">Channels</span> — choose where it works: web chat, WhatsApp, email, or API.</li>
                        <li><span class="font-semibold text-ink">Working hours &amp; escalation</span> — set availability and hand-off rules.</li>
                    </ul>
                </section>

                <section id="channels" class="scroll-mt-24">
                    <h2 class="font-display text-2xl font-black tracking-tight text-ink sm:text-3xl">Connecting channels</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-dim">Deploy your AI employee where your customers already are:</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-dim">
                        <li><span class="font-semibold text-ink">Web chat</span> — an embeddable widget you drop into any site.</li>
                        <li><span class="font-semibold text-ink">WhatsApp</span> — connect the WhatsApp Cloud API for the world's most popular channel.</li>
                        <li><span class="font-semibold text-ink">Email</span> — handle inboxes and follow-ups automatically.</li>
                        <li><span class="font-semibold text-ink">REST API</span> — build custom integrations on the documented v1 API.</li>
                        <li><span class="font-semibold text-ink">MCP tools</span> — connect Gmail, Calendar, CRM, and Microsoft 365.</li>
                    </ul>
                </section>
                <section id="wordpress" class="scroll-mt-24">
                    <h2 class="font-display text-2xl font-black tracking-tight text-ink sm:text-3xl">WordPress plugin</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-dim">Install the Nomdal WordPress plugin to sync your site with your AI employees.</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-dim">
                        <li>Sync <span class="font-semibold text-ink">contact-form leads</span> straight into Nomdal.</li>
                        <li>Sync <span class="font-semibold text-ink">WooCommerce products and orders</span> for two-way updates.</li>
                        <li>Let your AI employees answer product questions and take orders automatically.</li>
                    </ul>
                </section>

                <section id="automations" class="scroll-mt-24">
                    <h2 class="font-display text-2xl font-black tracking-tight text-ink sm:text-3xl">Building automations</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-dim">Automations follow a simple Trigger → Conditions → Actions flow.</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-dim">
                        <li><span class="font-semibold text-ink">6 triggers</span> — new lead, new order, and other business events.</li>
                        <li><span class="font-semibold text-ink">10 condition operators</span> — filter when an automation runs.</li>
                        <li><span class="font-semibold text-ink">7 action types</span> — send a message, create a task, update a record, and more.</li>
                    </ul>
                </section>

                <section id="appointments" class="scroll-mt-24">
                    <h2 class="font-display text-2xl font-black tracking-tight text-ink sm:text-3xl">Appointments &amp; scheduling</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-dim">Turn your AI employee into a receptionist that never misses a booking.</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-dim">
                        <li>Set <span class="font-semibold text-ink">availability, services, and time zones</span>.</li>
                        <li>Built-in <span class="font-semibold text-ink">conflict checks</span> prevent double-booking.</li>
                        <li>It <span class="font-semibold text-ink">books, reschedules, confirms, and reminds</span> customers automatically.</li>
                    </ul>
                </section>
            </div>
        </div>
    </section>
@endsection
