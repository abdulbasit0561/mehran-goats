<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Mehran Goats — Smart Goat Farm Investment Management</title>
    <meta name="description" content="Track livestock, manage investors, monitor capital & expenses, plan budgets, and automate profit distribution — all in one place.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}" />

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}" />
    <meta name="theme-color" content="#059669" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Mehran Goats" />
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ============ GOAT ANIMATIONS ============ --}}
    <style>
        @keyframes goat-float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-6px); }
        }
        @keyframes goat-bob {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-3px); }
        }
        @keyframes goat-bounce {
            0%, 100% { transform: translateY(0); }
            30%      { transform: translateY(-14px); }
            50%      { transform: translateY(0); }
            65%      { transform: translateY(-6px); }
        }
        @keyframes goat-wiggle {
            0%, 100% { transform: rotate(0deg); }
            25%      { transform: rotate(-6deg); }
            75%      { transform: rotate(6deg); }
        }
        @keyframes goat-walk {
            0%   { transform: translateX(-10vw) translateY(0); }
            48%  { transform: translateX(48vw) translateY(0); }
            50%  { transform: translateX(50vw) translateY(-4px); }
            52%  { transform: translateX(52vw) translateY(0); }
            100% { transform: translateX(110vw) translateY(0); }
        }
        @keyframes goat-fade-up {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .goat-bob    { animation: goat-bob 3s ease-in-out infinite; }
        .goat-float  { animation: goat-float 4s ease-in-out infinite; }
        .goat-bounce { animation: goat-bounce 2.2s ease-in-out infinite; }

        .goat-emoji {
            position: absolute;
            user-select: none;
            pointer-events: none;
            line-height: 1;
            z-index: 0;
        }

        /* Scroll reveal */
        .goat-reveal { opacity: 0; }
        .goat-reveal.is-visible { animation: goat-fade-up 0.7s ease forwards; }

        /* Feature-card icon wiggle on hover */
        .goat-feature { transition: transform 0.3s ease; }
        .goat-feature:hover .goat-wiggle-target {
            animation: goat-wiggle 0.6s ease-in-out;
        }

        @media (prefers-reduced-motion: reduce) {
            .goat-bob, .goat-float, .goat-bounce, .goat-emoji { animation: none !important; }
            .goat-reveal { opacity: 1 !important; }
            .goat-reveal.is-visible { animation: none !important; }
            .goat-feature:hover .goat-wiggle-target { animation: none !important; }
        }
    </style>
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    <x-pwa-install-banner />

    {{-- ==================== NAV BAR ==================== --}}
    <nav class="fixed top-0 inset-x-0 z-40 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="h-9 flex-shrink-0 goat-bob" fill="none">
                        <circle cx="32" cy="32" r="30" fill="url(#lg-grad)" />
                        <circle cx="32" cy="32" r="28" stroke="#ffffff" stroke-width="1" opacity="0.3" />
                        <g transform="translate(0, -2)">
                            <path d="M32 16 C24 16 20 22 20 30 C20 36 22 40 24 42 L22 46 C22 47 23 48 24 47 L26 45 C28 46 30 46.5 32 46.5 C34 46.5 36 46 38 45 L40 47 C41 48 42 47 42 46 L40 42 C42 40 44 36 44 30 C44 22 40 16 32 16 Z" fill="#ffffff"/>
                            <path d="M20 28 C16 27 13 28 12 30 C13 32 16 33 20 32 Z" fill="#ffffff" opacity="0.9"/>
                            <path d="M44 28 C48 27 51 28 52 30 C51 32 48 33 44 32 Z" fill="#ffffff" opacity="0.9"/>
                            <ellipse cx="27" cy="30" rx="2" ry="2.5" fill="#059669"/>
                            <ellipse cx="37" cy="30" rx="2" ry="2.5" fill="#059669"/>
                            <circle cx="29.5" cy="40" r="0.8" fill="#059669"/>
                            <circle cx="34.5" cy="40" r="0.8" fill="#059669"/>
                            <path d="M23 18 C19 14 16 12 14 14 C15 17 18 19 21 20 Z" fill="#fef3c7"/>
                            <path d="M41 18 C45 14 48 12 50 14 C49 17 46 19 43 20 Z" fill="#fef3c7"/>
                            <path d="M22 17 C19 14 17 13 15.5 14.5 C16.5 16 18.5 17.5 20.5 18 Z" fill="#fbbf24"/>
                            <path d="M42 17 C45 14 47 13 48.5 14.5 C47.5 16 45.5 17.5 43.5 18 Z" fill="#fbbf24"/>
                            <path d="M30 45 L31 50 L32 52 L33 50 L34 45 Z" fill="#ffffff" opacity="0.9"/>
                        </g>
                        <defs>
                            <linearGradient id="lg-grad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#10b981"/>
                                <stop offset="100%" stop-color="#059669"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="flex flex-col leading-none">
                        <span class="text-base font-extrabold text-gray-800 tracking-tight">Mehran Goats</span>
                        <span class="text-[9px] font-medium text-gray-400 tracking-wider uppercase">Farm Management</span>
                    </div>
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">
                            Log in
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- ==================== HERO ==================== --}}
    <section class="relative pt-28 pb-16 sm:pt-36 sm:pb-24 overflow-hidden">
        {{-- Background decoration --}}
        <div class="absolute inset-0 -z-10">
            <div class="absolute top-0 right-0 w-72 h-72 sm:w-96 sm:h-96 bg-emerald-100 rounded-full blur-3xl opacity-60"></div>
            <div class="absolute bottom-0 left-0 w-72 h-72 sm:w-96 sm:h-96 bg-teal-100 rounded-full blur-3xl opacity-40"></div>
        </div>

        {{-- Floating goat emojis --}}
        <span class="goat-emoji goat-float text-4xl sm:text-5xl" style="top:18%;left:8%;opacity:.5;animation-delay:0s">🐐</span>
        <span class="goat-emoji goat-float text-3xl sm:text-4xl" style="top:30%;right:10%;opacity:.4;animation-delay:1s">🐐</span>
        <span class="goat-emoji goat-float text-2xl sm:text-3xl" style="top:62%;left:14%;opacity:.35;animation-delay:2s">🐐</span>
        <span class="goat-emoji goat-float text-3xl sm:text-4xl" style="top:68%;right:16%;opacity:.3;animation-delay:1.5s">🐐</span>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto goat-reveal">
                <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 px-4 py-1.5 rounded-full text-xs font-semibold mb-6">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    Smart Goat Farm Investment Platform
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight tracking-tight">
                    Manage Your Goat Farm
                    <span class="text-emerald-600">Investments</span>
                    with Confidence
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-gray-500 leading-relaxed">
                    Track livestock, manage investors, monitor capital &amp; expenses, plan budgets, and automate profit distribution — all in one place.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 bg-emerald-600 text-white rounded-xl text-base font-semibold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/20">
                            Go to Dashboard →
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 bg-emerald-600 text-white rounded-xl text-base font-semibold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/20">
                            Get Started →
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== STATS BAND ==================== --}}
    <section class="relative bg-emerald-600 py-12 overflow-hidden">
        {{-- Grazing goats walking across the band --}}
        <span class="goat-emoji text-xl" style="bottom:6px;left:0;opacity:.25;animation:goat-walk 20s linear infinite;animation-delay:0s">🐐</span>
        <span class="goat-emoji text-2xl" style="bottom:6px;left:0;opacity:.2;animation:goat-walk 26s linear infinite;animation-delay:8s">🐐</span>
        <span class="goat-emoji text-lg" style="bottom:6px;left:0;opacity:.18;animation:goat-walk 30s linear infinite;animation-delay:14s">🐐</span>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div>
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">100%</p>
                    <p class="mt-1 text-sm text-emerald-100">Investment Tracking</p>
                </div>
                <div>
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">Real-time</p>
                    <p class="mt-1 text-sm text-emerald-100">Profit &amp; Loss</p>
                </div>
                <div>
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">Multi</p>
                    <p class="mt-1 text-sm text-emerald-100">Investor Support</p>
                </div>
                <div>
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">Auto</p>
                    <p class="mt-1 text-sm text-emerald-100">Profit Distribution</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FEATURES ==================== --}}
    <section class="py-16 sm:py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                    Everything You Need to Run a Profitable Farm
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    From livestock tracking to investor settlements — powerful tools designed for goat farm investors.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                {{-- Livestock Tracking --}}
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 hover:shadow-md transition goat-feature goat-reveal" style="animation-delay:0s">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-emerald-600 goat-wiggle-target" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Livestock Tracking</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Track every goat by breed, weight, status, and purchase/sale price. Monitor your entire herd value in real time.
                    </p>
                </div>

                {{-- Investor Management --}}
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 hover:shadow-md transition goat-feature goat-reveal" style="animation-delay:.12s">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-blue-600 goat-wiggle-target" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Investor Management</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Manage multiple investors with individual capital contributions, ownership percentages, and settlement balances.
                    </p>
                </div>

                {{-- Financial Dashboard --}}
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 hover:shadow-md transition goat-feature goat-reveal" style="animation-delay:.24s">
                    <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-teal-600 goat-wiggle-target" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Financial Dashboard</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Get a bird's-eye view of total capital, herd value, realized profit, expenses, net profit, and return percentage.
                    </p>
                </div>

                {{-- Budget Planning --}}
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 hover:shadow-md transition goat-feature goat-reveal" style="animation-delay:.36s">
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-amber-600 goat-wiggle-target" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Budget Planning</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Plan income and expenses month by month with an interactive budget planner. Track actual vs. planned performance.
                    </p>
                </div>

                {{-- Health & Breeding --}}
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 hover:shadow-md transition goat-feature goat-reveal" style="animation-delay:.48s">
                    <div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-rose-600 goat-wiggle-target" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Health &amp; Breeding</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Log vaccinations, treatments, and breeding records for each goat. Keep your livestock healthy and productive.
                    </p>
                </div>

                {{-- Profit/Loss Reports --}}
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 hover:shadow-md transition goat-feature goat-reveal" style="animation-delay:.60s">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-indigo-600 goat-wiggle-target" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Profit &amp; Loss Reports</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Automate profit distribution across investors based on ownership. Generate individual statements and export to CSV.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== HOW IT WORKS ==================== --}}
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900">
                    Up and Running in Minutes
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    No spreadsheets, no complex setup. Just sign up and start tracking.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">
                {{-- Step 1 --}}
                <div class="text-center goat-reveal" style="animation-delay:0s">
                    <div class="w-16 h-16 mx-auto bg-emerald-600 text-white rounded-2xl flex items-center justify-center text-2xl font-extrabold mb-5 shadow-lg shadow-emerald-600/20">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Create Your Account</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Sign up and add your investors with their capital contributions and ownership percentages.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="text-center goat-reveal" style="animation-delay:.15s">
                    <div class="w-16 h-16 mx-auto bg-emerald-600 text-white rounded-2xl flex items-center justify-center text-2xl font-extrabold mb-5 shadow-lg shadow-emerald-600/20">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Add Your Livestock</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Register your goats with breed, weight, purchase price, and health records. Track every animal individually.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="text-center goat-reveal" style="animation-delay:.3s">
                    <div class="w-16 h-16 mx-auto bg-emerald-600 text-white rounded-2xl flex items-center justify-center text-2xl font-extrabold mb-5 shadow-lg shadow-emerald-600/20">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Track &amp; Distribute</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Log expenses, monitor profit/loss, and distribute returns to investors automatically based on their shares.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== CTA ==================== --}}
    <section class="py-16 sm:py-24 bg-gradient-to-br from-emerald-600 to-emerald-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="w-16 h-16 mx-auto mb-6 goat-bounce" fill="none">
                <circle cx="32" cy="32" r="30" fill="rgba(255,255,255,0.1)"/>
                <g transform="translate(0, -2)">
                    <path d="M32 16 C24 16 20 22 20 30 C20 36 22 40 24 42 L22 46 C22 47 23 48 24 47 L26 45 C28 46 30 46.5 32 46.5 C34 46.5 36 46 38 45 L40 47 C41 48 42 47 42 46 L40 42 C42 40 44 36 44 30 C44 22 40 16 32 16 Z" fill="#ffffff" opacity="0.95"/>
                    <ellipse cx="27" cy="30" rx="2" ry="2.5" fill="#059669"/>
                    <ellipse cx="37" cy="30" rx="2" ry="2.5" fill="#059669"/>
                    <path d="M23 18 C19 14 16 12 14 14 C15 17 18 19 21 20 Z" fill="#fbbf24"/>
                    <path d="M41 18 C45 14 48 12 50 14 C49 17 46 19 43 20 Z" fill="#fbbf24"/>
                </g>
            </svg>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                Ready to Manage Your Farm Smarter?
            </h2>
            <p class="mt-4 text-lg text-emerald-100">
                Join Mehran Goats today and take control of your livestock investments.
            </p>
            <div class="mt-8">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-8 py-3.5 bg-white text-emerald-700 rounded-xl text-base font-bold hover:bg-emerald-50 transition shadow-lg">
                        Go to Dashboard →
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-3.5 bg-white text-emerald-700 rounded-xl text-base font-bold hover:bg-emerald-50 transition shadow-lg">
                        Get Started →
                    </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- ==================== FOOTER ==================== --}}
    <footer class="bg-gray-900 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="h-9 flex-shrink-0 goat-float" fill="none">
                        <circle cx="32" cy="32" r="30" fill="#059669"/>
                        <g transform="translate(0, -2)">
                            <path d="M32 16 C24 16 20 22 20 30 C20 36 22 40 24 42 L22 46 C22 47 23 48 24 47 L26 45 C28 46 30 46.5 32 46.5 C34 46.5 36 46 38 45 L40 47 C41 48 42 47 42 46 L40 42 C42 40 44 36 44 30 C44 22 40 16 32 16 Z" fill="#ffffff"/>
                            <ellipse cx="27" cy="30" rx="2" ry="2.5" fill="#059669"/>
                            <ellipse cx="37" cy="30" rx="2" ry="2.5" fill="#059669"/>
                            <path d="M23 18 C19 14 16 12 14 14 C15 17 18 19 21 20 Z" fill="#fbbf24"/>
                            <path d="M41 18 C45 14 48 12 50 14 C49 17 46 19 43 20 Z" fill="#fbbf24"/>
                        </g>
                    </svg>
                    <div class="flex flex-col leading-none">
                        <span class="text-sm font-extrabold text-white tracking-tight">Mehran Goats</span>
                        <span class="text-[9px] font-medium text-gray-500 tracking-wider uppercase">Farm Management</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 sm:gap-6">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-white transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-white transition">Log in</a>
                    @endauth
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-800 text-center">
                <p class="text-sm text-gray-500">&copy; {{ date('Y') }} Mehran Goats. All rights reserved.</p>
            </div>
        </div>
    </footer>

    {{-- Scroll-reveal: add .is-visible to .goat-reveal elements as they enter the viewport --}}
    <script>
        (function () {
            var reveals = document.querySelectorAll('.goat-reveal');
            var reduce  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (reduce || !('IntersectionObserver' in window)) {
                reveals.forEach(function (el) { el.classList.add('is-visible'); });
                return;
            }

            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });

            reveals.forEach(function (el) { io.observe(el); });
        })();
    </script>

</body>
</html>
