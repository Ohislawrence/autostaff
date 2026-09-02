<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Nomdal — AI Employees for Your Business')</title>
    <meta name="description" content="@yield('meta_description', 'Nomdal builds AI employees that handle conversations, orders, appointments, and follow-ups — built for businesses in Nigeria and everywhere else.')">
    <meta property="og:title" content="@yield('og_title', 'Nomdal — AI Employees for Your Business')">
    <meta property="og:description" content="@yield('og_description', 'AI employees that work your front desk, sales, and support — 24/7.')">
    <meta property="og:type" content="website">
    <meta name="theme-color" content="#060613">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500,600" rel="stylesheet" />
    <link href="https://api.fontshare.com/v2/css?f[]=clash-display@500,600,700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Clash Display"', 'Inter', 'ui-sans-serif', 'system-ui'],
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular'],
                    },
                    colors: {
                        void: { DEFAULT: '#060613', 50: '#0A0A1A', 100: '#10101F' },
                        surface: { DEFAULT: '#10101F', 50: '#161629', 100: '#1C1C33' },
                        violet: { 400: '#9A8CFF', 500: '#7C5CFF', 600: '#6A47F2', 900: '#241A66' },
                        cyan: { 400: '#6FF0FF', 500: '#35E8FF', 600: '#1FC7DE' },
                        amber: { 400: '#FFC978', 500: '#FFB84D', 600: '#F2A02E' },
                        ink: { DEFAULT: '#F5F5FA', dim: '#B8B8CC', faint: '#6E6E8A' },
                    },
                    boxShadow: {
                        glow: '0 0 40px -8px rgba(124,92,255,0.45)',
                        'glow-cyan': '0 0 40px -8px rgba(53,232,255,0.35)',
                    },
                    keyframes: {
                        drift: {
                            '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                            '50%': { transform: 'translate(3%, -4%) scale(1.05)' },
                        },
                        pulseLine: {
                            '0%': { strokeDashoffset: '240', opacity: '0' },
                            '15%': { opacity: '1' },
                            '80%': { opacity: '1' },
                            '100%': { strokeDashoffset: '0', opacity: '0' },
                        },
                        nodePulse: {
                            '0%, 100%': { transform: 'scale(1)', opacity: '0.6' },
                            '50%': { transform: 'scale(1.6)', opacity: '0' },
                        },
                        ticker: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-50%)' },
                        },
                        rise: {
                            '0%': { opacity: '0', transform: 'translateY(14px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                    },
                    animation: {
                        drift: 'drift 18s ease-in-out infinite',
                        'drift-slow': 'drift 26s ease-in-out infinite reverse',
                        'pulse-line': 'pulseLine 3.5s ease-in-out infinite',
                        'node-pulse': 'nodePulse 2.4s ease-out infinite',
                        ticker: 'ticker 32s linear infinite',
                        rise: 'rise 0.7s cubic-bezier(0.16,1,0.3,1) both',
                    },
                },
            },
        };
    </script>

    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
            }
        }

        html { background: #060613; }

        .bg-grid {
            background-image: radial-gradient(rgba(124,92,255,0.16) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .bg-fade {
            -webkit-mask-image: linear-gradient(to bottom, black, black 60%, transparent 100%);
            mask-image: linear-gradient(to bottom, black, black 60%, transparent 100%);
        }

        .ticker-track { animation: ticker 32s linear infinite; }
        .ticker-track:hover { animation-play-state: paused; }

        ::selection { background: #7C5CFF; color: #060613; }

        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #0A0A1A; }
        ::-webkit-scrollbar-thumb { background: #241A66; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #6A47F2; }

        :focus-visible {
            outline: 2px solid #35E8FF;
            outline-offset: 3px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="min-h-screen bg-void font-sans text-ink antialiased">
    <div class="pointer-events-none fixed inset-0 -z-10 bg-grid bg-fade">
        <div class="absolute -top-32 -left-20 h-[32rem] w-[32rem] rounded-full bg-violet-600/25 blur-[120px] animate-drift"></div>
        <div class="absolute top-1/3 -right-32 h-[28rem] w-[28rem] rounded-full bg-cyan-500/15 blur-[130px] animate-drift-slow"></div>
        <div class="absolute bottom-0 left-1/4 h-[24rem] w-[24rem] rounded-full bg-amber-500/10 blur-[120px] animate-drift"></div>
    </div>

    @include('frontpage.partials.header')

    <main class="relative z-10 flex-1">
        @yield('content')
    </main>

    @include('frontpage.partials.footer')
</body>
</html>
