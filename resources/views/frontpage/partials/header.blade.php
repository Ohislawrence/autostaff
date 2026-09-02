<header
    x-data="{ open: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 12)"
    class="sticky top-0 z-50 transition-all duration-300"
    :class="scrolled ? 'bg-bone/85 backdrop-blur-xl border-b border-ink/10' : 'bg-transparent border-b border-transparent'"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
        <a href="{{ route('frontpage.home') }}" class="group flex items-center gap-2.5">
            <img src="{{ asset('images/nomdal-favicon.png') }}" alt="Nomdal" class="h-8 w-8 object-contain" />
            <span class="font-display text-lg font-black tracking-tight text-ink">Nomdal</span>
        </a>

        <nav class="hidden items-center gap-8 md:flex" aria-label="Primary">
            <a href="{{ route('frontpage.product') }}" class="link-underline text-sm font-semibold text-ink-dim transition hover:text-ink">Product</a>
            <a href="{{ route('frontpage.templates') }}" class="link-underline text-sm font-semibold text-ink-dim transition hover:text-ink">AI Employees</a>
            <a href="{{ route('frontpage.marketplace') }}" class="link-underline text-sm font-semibold text-ink-dim transition hover:text-ink">Plugins</a>
            <a href="{{ route('frontpage.pricing') }}" class="link-underline text-sm font-semibold text-ink-dim transition hover:text-ink">Pricing</a>
        </nav>

        <div class="hidden items-center gap-4 md:flex">
            <a href="{{ route('login') }}" class="link-underline text-sm font-semibold text-ink-dim transition hover:text-ink">Sign in</a>
            <a href="{{ route('login') }}"
               class="group inline-flex items-center gap-2 rounded-full bg-ink px-5 py-2 text-sm font-bold text-bone transition hover:bg-forest">
                Start free
                <span class="relative flex h-1.5 w-1.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-lime opacity-75"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-lime"></span>
                </span>
            </a>
        </div>

        <button @click="open = !open" class="md:hidden text-ink" aria-label="Toggle menu" :aria-expanded="open">
            <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg x-show="open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div x-show="open" x-collapse x-cloak class="md:hidden border-t border-ink/10 bg-bone/95 backdrop-blur-xl px-6 py-4">
        <nav class="flex flex-col gap-4" aria-label="Mobile">
            <a href="{{ route('frontpage.product') }}" class="text-sm font-semibold text-ink-dim">Product</a>
            <a href="{{ route('frontpage.templates') }}" class="text-sm font-semibold text-ink-dim">AI Employees</a>
            <a href="{{ route('frontpage.marketplace') }}" class="text-sm font-semibold text-ink-dim">Plugins</a>
            <a href="{{ route('frontpage.pricing') }}" class="text-sm font-semibold text-ink-dim">Pricing</a>
            <a href="{{ route('login') }}" class="text-sm font-semibold text-ink-dim">Sign in</a>
            <a href="{{ route('login') }}" class="rounded-full bg-ink px-4 py-2 text-center text-sm font-bold text-bone">Start free</a>
        </nav>
    </div>
</header>
