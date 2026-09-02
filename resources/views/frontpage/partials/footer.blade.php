<footer class="relative z-10 border-t border-white/10 bg-surface/60">
    <div class="mx-auto max-w-7xl px-6 py-14">
        <div class="grid grid-cols-2 gap-10 md:grid-cols-5">
            <div class="col-span-2">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-cyan-500">
                        <span class="font-display text-xs font-bold text-void">A</span>
                    </span>
                    <span class="font-display text-base font-semibold text-ink">Nomdal</span>
                </div>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-faint">
                    AI employees that answer, sell, and follow up — built for businesses in Nigeria, and everywhere your customers are.
                </p>
            </div>
            <div>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-faint">Product</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-dim">
                    <li><a href="{{ route('frontpage.templates') }}" class="hover:text-ink">AI Employees</a></li>
                    <li><a href="{{ route('frontpage.pricing') }}" class="hover:text-ink">Pricing</a></li>
                    <li><a href="{{ route('frontpage.integrations') }}" class="hover:text-ink">Integrations</a></li>
                </ul>
            </div>
            <div>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-faint">Company</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-dim">
                    <li><a href="{{ route('frontpage.about') }}" class="hover:text-ink">About</a></li>
                    <li><a href="{{ route('frontpage.contact') }}" class="hover:text-ink">Contact</a></li>
                </ul>
            </div>
            <div>
                <p class="font-mono text-xs uppercase tracking-wider text-ink-faint">Legal</p>
                <ul class="mt-4 space-y-3 text-sm text-ink-dim">
                    <li><a href="{{ route('frontpage.privacy') }}" class="hover:text-ink">Privacy</a></li>
                    <li><a href="{{ route('frontpage.terms') }}" class="hover:text-ink">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs text-ink-faint md:flex-row">
            <p>&copy; {{ date('Y') }} Nomdal. Built in Lagos, for the world.</p>
            <p class="font-mono">v1.0 · all systems operational</p>
        </div>
    </div>
</footer>
