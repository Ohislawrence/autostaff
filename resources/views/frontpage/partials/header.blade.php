<header
    x-data="{ open: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 12)"
    class="sticky top-0 z-50 transition-all duration-300"
    :class="scrolled ? 'bg-void/80 backdrop-blur-xl border-b border-white/10' : 'bg-transparent border-b border-transparent'"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
        <a href="{{ route('frontpage.home') }}" class="group flex items-center gap-2.5">
            <span class="relative flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-cyan-500 shadow-glow">
                <span class="absolute inset-0 rounded-lg bg-violet-500 animate-node-pulse"></span>
                <span class="relative font-display text-sm font-bold text-void">A</span>
            </span>
            <span class="font-display text-lg font-semibold tracking-tight text-ink">Nomdal</span>
        </a>

        <nav class="hidden items-center gap-8 md:flex" aria-label="Primary">
            <a href="{{ route('frontpage.product') }}" class="text-sm font-medium text-ink-dim transition hover:text-ink">Product</a>
            <a href="{{ route('frontpage.templates') }}" class="text-sm font-medium text-ink-dim transition hover:text-ink">AI Employees</a>
            <a href="{{ route('frontpage.marketplace') }}" class="text-sm font-medium text-ink-dim transition hover:text-ink">Plugins</a>
            <a href="{{ route('frontpage.pricing') }}" class="text-sm font-medium text-ink-dim transition hover:text-ink">Pricing</a>
            <a href="{{ route('frontpage.docs') }}" class="text-sm font-medium text-ink-dim transition hover:text-ink">Docs</a>
        </nav>

        <div class="hidden items-center gap-3 md:flex">
            <a href="{{ route('login') }}" class="text-sm font-medium text-ink-dim transition hover:text-ink">Sign in</a>
            <a href="{{ route('login') }}"
               class="group relative overflow-hidden rounded-full bg-gradient-to-r from-violet-500 to-cyan-500 px-5 py-2 text-sm font-semibold text-void shadow-glow transition hover:shadow-glow-cyan">
                <span class="relative">Start free</span>
            </a>
        </div>

        <button @click="open = !open" class="md:hidden text-ink" aria-label="Toggle menu" :aria-expanded="open">
            <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg x-show="open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div x-show="open" x-collapse x-cloak class="md:hidden border-t border-white/10 bg-void/95 backdrop-blur-xl px-6 py-4">
        <nav class="flex flex-col gap-4" aria-label="Mobile">
            <a href="{{ route('frontpage.product') }}" class="text-sm font-medium text-ink-dim">Product</a>
            <a href="{{ route('frontpage.templates') }}" class="text-sm font-medium text-ink-dim">AI Employees</a>
            <a href="{{ route('frontpage.marketplace') }}" class="text-sm font-medium text-ink-dim">Plugins</a>
            <a href="{{ route('frontpage.pricing') }}" class="text-sm font-medium text-ink-dim">Pricing</a>
            <a href="{{ route('frontpage.docs') }}" class="text-sm font-medium text-ink-dim">Docs</a>
            <a href="{{ route('login') }}" class="text-sm font-medium text-ink-dim">Sign in</a>
            <a href="{{ route('login') }}" class="rounded-full bg-gradient-to-r from-violet-500 to-cyan-500 px-4 py-2 text-center text-sm font-semibold text-void">Start free</a>
        </nav>
    </div>
</header>
