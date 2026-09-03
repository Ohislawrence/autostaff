<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Nomdal — AI Employees for Your Business')</title>
    <meta name="description" content="@yield('meta_description', 'Nomdal builds AI employees that handle conversations, orders, appointments, and follow-ups — built for businesses in Nigeria and everywhere else.')">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Nomdal">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="@yield('title', 'Nomdal — AI Employees for Your Business')">
    <meta property="og:description" content="@yield('meta_description', 'Nomdal builds AI employees that handle conversations, orders, appointments, and follow-ups — built for businesses in Nigeria and everywhere else.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url('/images/og-image2.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Nomdal — AI Employees for Your Business')">
    <meta name="twitter:description" content="@yield('meta_description', 'Nomdal builds AI employees that handle conversations, orders, appointments, and follow-ups — built for businesses in Nigeria and everywhere else.')">
    <meta name="twitter:image" content="{{ url('/images/og-image2.png') }}">

    <meta name="theme-color" content="#e8ebff">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo:500,600,700,800,900|inter:400,500,600|jetbrains-mono:400,500" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Archivo"', 'Inter', 'ui-sans-serif', 'system-ui'],
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular'],
                    },
                    colors: {
                        bone: '#fbf7ef',
                        purple: '#e8ebff',
                        periwinkle: { DEFAULT: '#899bff', 600: '#6f83f2', 700: '#5a6fe0' },
                        lime: '#3ab91a',
                        citron: '#caeb65',
                        blush: '#f5b2bd',
                        peach: '#ffc4ac',
                        forest: '#2a5b40',
                        wine: '#63252f',
                        rust: '#cf530b',
                        sand: '#c3b591',
                        ltbeige: '#ebe3d4',
                        ink: { DEFAULT: '#1a1a1a', dim: '#55504a', faint: '#8a857c' },
                    },
                    keyframes: {
                        drift: {
                            '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                            '50%': { transform: 'translate(4%, -5%) scale(1.06)' },
                        },
                        radiate: {
                            '0%': { transform: 'scale(1)', opacity: '1' },
                            '100%': { transform: 'scale(2.4)', opacity: '0' },
                        },
                        ticker: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-50%)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-18px)' },
                        },
                        spinSlow: {
                            '0%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(360deg)' },
                        },
                    },
                    animation: {
                        drift: 'drift 20s ease-in-out infinite',
                        'drift-slow': 'drift 28s ease-in-out infinite reverse',
                        radiate: 'radiate 2.6s cubic-bezier(0.16, 1, 0.3, 1) infinite',
                        ticker: 'ticker 36s linear infinite',
                        float: 'float 7s ease-in-out infinite',
                        'spin-slow': 'spinSlow 45s linear infinite',
                    },
                },
            },
        };
    </script>

    <style>
        html { background: #e8ebff; }
        body { background: #fbf7ef; }

        [x-cloak] { display: none !important; }

        ::selection { background: #3ab91a; color: #1a1a1a; }

        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); will-change: opacity, transform; }
        .reveal.on { opacity: 1; transform: translateY(0); }

        .ticker-track { animation: ticker 36s linear infinite; }
        .ticker-track:hover { animation-play-state: paused; }

        .link-underline { position: relative; }
        .link-underline::after { content: ''; position: absolute; left: 0; bottom: -3px; height: 2px; width: 100%; background: currentColor; transform: scaleX(0); transform-origin: right; transition: transform .3s cubic-bezier(.45,0,.55,1); }
        .link-underline:hover::after { transform: scaleX(1); transform-origin: left; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
            .reveal { opacity: 1 !important; transform: none !important; }
        }

        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #e8ebff; }
        ::-webkit-scrollbar-thumb { background: #c3b591; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #899bff; }

        :focus-visible { outline: 2px solid #63252f; outline-offset: 3px; border-radius: 4px; }
    </style>

    {{-- Structured data --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Nomdal",
        "url": "{{ url('/') }}",
        "logo": "{{ url('/images/og-image.png') }}",
        "email": "hello@nomdal.com",
        "telephone": "+2349022239628",
        "address": { "@type": "PostalAddress", "addressLocality": "Lagos", "addressCountry": "NG" }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Nomdal",
        "url": "{{ url('/') }}"
    }
    </script>
</head>
<body class="min-h-screen bg-bone font-sans text-ink antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 -left-24 h-[30rem] w-[30rem] rounded-full bg-blush/50 blur-[120px] animate-drift"></div>
        <div class="absolute top-1/4 -right-32 h-[26rem] w-[26rem] rounded-full bg-periwinkle/40 blur-[120px] animate-drift-slow"></div>
        <div class="absolute bottom-0 left-1/3 h-[22rem] w-[22rem] rounded-full bg-citron/50 blur-[110px] animate-drift"></div>
    </div>

    @include('frontpage.partials.header')

    <main class="relative z-10 flex-1">
        @yield('content')
    </main>

    @include('frontpage.partials.footer')

    <script>
        (function () {
            var els = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window)) { els.forEach(function (el) { el.classList.add('on'); }); return; }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) { entry.target.classList.add('on'); io.unobserve(entry.target); }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -48px 0px' });
            els.forEach(function (el) {
                var d = el.getAttribute('data-reveal-delay');
                if (d) { el.style.transitionDelay = d + 'ms'; }
                io.observe(el);
            });
        })();
    </script>
</body>
</html>
