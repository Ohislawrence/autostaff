<footer class="relative z-10 border-t border-ink/10 bg-purple/40">
    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="grid grid-cols-2 gap-10 md:grid-cols-5">
            <div class="col-span-2">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/nomdal-favicon.png') }}" alt="Nomdal" class="h-7 w-7 object-contain" />
                    <span class="font-display text-base font-black text-ink">Nomdal</span>
                </div>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-dim">
                    AI employees that actually work for your business — selling, supporting, booking, ordering, and handling admin around the clock.
                </p>
            </div>
            <div>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-faint">Product</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-dim">
                    <li><a href="{{ route('frontpage.templates') }}" class="link-underline hover:text-ink">AI Employees</a></li>
                    <li><a href="{{ route('frontpage.pricing') }}" class="link-underline hover:text-ink">Pricing</a></li>
                    <li><a href="{{ route('frontpage.integrations') }}" class="link-underline hover:text-ink">Integrations</a></li>
                    <li><a href="{{ route('frontpage.docs') }}" class="link-underline hover:text-ink">Docs</a></li>
                </ul>
            </div>
            <div>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-faint">Company</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-dim">
                    <li><a href="{{ route('frontpage.about') }}" class="link-underline hover:text-ink">About</a></li>
                    <li><a href="{{ route('frontpage.contact') }}" class="link-underline hover:text-ink">Contact</a></li>
                </ul>
            </div>
            <div>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-faint">Legal</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-dim">
                    <li><a href="{{ route('frontpage.privacy') }}" class="link-underline hover:text-ink">Privacy</a></li>
                    <li><a href="{{ route('frontpage.terms') }}" class="link-underline hover:text-ink">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-ink/10 pt-6 text-xs text-ink-faint md:flex-row">
            <p>&copy; {{ date('Y') }} Nomdal. Built in Lagos, for the world.</p>
            <p class="font-mono">v1.0 · all systems operational</p>
        </div>
    </div>
</footer>
