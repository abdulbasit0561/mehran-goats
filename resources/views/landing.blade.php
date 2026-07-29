@php
    $s = function($key, $default = '') use ($settings) {
        return $settings[$key] ?? $default;
    };

    // Branding — page title falls back to SEO title then site name
    $pageTitle    = $s('site_page_title') ?: $s('seo_title', 'Mehran Goats');
    $themeColor    = $s('site_theme_color', '#059669');
    $customLogo    = $s('site_logo');
    $customFavicon = $s('site_favicon');
    $customApple   = $s('site_apple_icon');
    $siteName      = $s('site_name', 'Mehran Goats');
    $siteTagline   = $s('site_tagline', 'Livestock Company');

    // SEO
    $seoTitle       = $s('seo_title', 'Mehran Goats — Premium Goat Farming & Livestock Company');
    $seoDescription = $s('seo_description', 'Mehran Goats is a premier goat farming company specializing in livestock breeding, investment management, and sustainable farming solutions.');
    $seoKeywords    = $s('seo_keywords');
    $robotsIndex    = $s('seo_robots_index', 'index');
    $robotsFollow   = $s('seo_robots_follow', 'follow');
    $canonicalUrl   = $s('seo_canonical_url') ?: url('/');
    $ogImage        = $s('seo_og_image');
    $ogType         = $s('seo_og_type', 'website');
    $twitterCard    = $s('seo_twitter_card', 'summary_large_image');
    $schemaType     = $s('seo_structured_data_type', 'Organization');
    $schemaName     = $s('seo_structured_data_name', $siteName);
    $schemaUrl      = $s('seo_structured_data_url') ?: url('/');
    $schemaLogo     = $s('seo_structured_data_logo');
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type'    => $schemaType,
        'name'     => $schemaName,
        'url'      => $schemaUrl,
    ];
    if ($schemaLogo) {
        $structuredData['logo'] = asset($schemaLogo);
    } elseif ($customLogo) {
        $structuredData['logo'] = asset('storage/' . $customLogo);
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Page Title --}}
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    @if ($seoKeywords)
    <meta name="keywords" content="{{ $seoKeywords }}">
    @endif
    <meta name="robots" content="{{ $robotsIndex }}, {{ $robotsFollow }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    @if ($ogImage)
    <meta property="og:image" content="{{ asset($ogImage) }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    @if ($ogImage)
    <meta name="twitter:image" content="{{ asset($ogImage) }}">
    @endif

    {{-- Structured Data (JSON-LD) --}}
    <script type="application/ld+json">
    {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
    </script>

    {{-- Favicon --}}
    @if ($customFavicon)
        <link rel="icon" href="{{ asset('storage/' . $customFavicon) }}">
        <link rel="shortcut icon" href="{{ asset('storage/' . $customFavicon) }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}" />
    @endif

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}" />
    <meta name="theme-color" content="{{ $themeColor }}" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="{{ $siteName }}" />
    @if ($customApple)
        <link rel="apple-touch-icon" href="{{ asset('storage/' . $customApple) }}">
    @else
        <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" />
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes mg-float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-6px); }
        }
        @keyframes mg-fade-up {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes mg-count {
            from { opacity: 0.4; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        .mg-bob   { animation: mg-float 4s ease-in-out infinite; }
        .mg-float { animation: mg-float 5s ease-in-out infinite; }

        .mg-reveal { opacity: 0; }
        .mg-reveal.is-visible { animation: mg-fade-up 0.7s ease forwards; }

        .mg-emoji {
            position: absolute;
            user-select: none;
            pointer-events: none;
            line-height: 1;
            z-index: 0;
        }

        .mg-counter { animation: mg-count 0.8s ease forwards; }

        .mg-hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .mg-hover-lift:hover { transform: translateY(-4px); }

        @media (prefers-reduced-motion: reduce) {
            .mg-bob, .mg-float, .mg-emoji { animation: none !important; }
            .mg-reveal { opacity: 1 !important; }
            .mg-reveal.is-visible { animation: none !important; }
            .mg-hover-lift:hover { transform: none; }
        }
    </style>
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    <x-pwa-install-banner />

    {{-- ==================== NAV BAR ==================== --}}
    <nav class="fixed top-0 inset-x-0 z-40 bg-white/90 backdrop-blur-md border-b border-gray-100 transition-all" id="mg-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center gap-2.5">
                    @if ($customLogo)
                        <img src="{{ asset('storage/' . $customLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto flex-shrink-0 mg-bob object-contain">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="h-9 flex-shrink-0 mg-bob" fill="none">
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
                    @endif
                    <div class="flex flex-col leading-none">
                        <span class="text-base font-extrabold text-gray-800 tracking-tight">{{ $siteName }}</span>
                        <span class="text-[9px] font-medium text-gray-400 tracking-wider uppercase">{{ $siteTagline }}</span>
                    </div>
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden md:flex items-center gap-7">
                    <a href="#about" class="text-sm font-medium text-gray-600 hover:text-emerald-600 transition">About</a>
                    <a href="#services" class="text-sm font-medium text-gray-600 hover:text-emerald-600 transition">Services</a>
                    <a href="#breeds" class="text-sm font-medium text-gray-600 hover:text-emerald-600 transition">Our Breeds</a>
                    <a href="#why-us" class="text-sm font-medium text-gray-600 hover:text-emerald-600 transition">Why Us</a>
                    <a href="#contact" class="text-sm font-medium text-gray-600 hover:text-emerald-600 transition">Contact</a>
                </div>

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

    {{-- ==================== FARM VIDEO SECTION ==================== --}}
    <section class="relative h-[380px] sm:h-[460px] lg:h-[520px] overflow-hidden" id="farm-scene">
        {{-- Background video --}}
        <video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline
               poster="{{ asset('videos/farm-poster.jpg') }}">
            <source src="{{ asset('videos/farm-hero.mp4') }}" type="video/mp4">
        </video>

        {{-- Dark gradient overlay for text readability --}}
        <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-black/20 to-black/50"></div>

        {{-- Overlay text banner --}}
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center px-4">
                <p class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight" style="text-shadow: 0 2px 12px rgba(0,0,0,0.5);">
                    {{ $s('farm_video_title', 'Welcome to Our Farm') }}
                </p>
                <p class="mt-3 text-base sm:text-lg text-white/90 max-w-xl mx-auto" style="text-shadow: 0 1px 6px rgba(0,0,0,0.5);">
                    {{ $s('farm_video_subtitle') }}
                </p>
            </div>
        </div>
    </section>

    {{-- ==================== HERO ==================== --}}
    <section class="relative pt-12 pb-12 sm:pt-16 sm:pb-16 overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-teal-50">
        {{-- Background decoration --}}
        <div class="absolute inset-0 -z-10">
            <div class="absolute top-0 right-0 w-72 h-72 sm:w-96 sm:h-96 bg-emerald-100 rounded-full blur-3xl opacity-60"></div>
            <div class="absolute bottom-0 left-0 w-72 h-72 sm:w-96 sm:h-96 bg-teal-100 rounded-full blur-3xl opacity-40"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                {{-- Left: Text --}}
                <div class="mg-reveal">
                    <div class="inline-flex items-center gap-2 bg-white text-emerald-700 px-4 py-1.5 rounded-full text-xs font-semibold mb-6 shadow-sm border border-emerald-100">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        {{ $s('hero_badge') }}
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight tracking-tight">
                        {{ $s('hero_title') }}
                    </h1>

                    <p class="mt-6 text-lg sm:text-xl text-gray-500 leading-relaxed max-w-xl">
                        {{ $s('hero_subtitle') }}
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                        <a href="#services" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 bg-emerald-600 text-white rounded-xl text-base font-semibold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/20">
                            {{ $s('hero_cta_primary_text') }} →
                        </a>
                        <a href="#contact" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 bg-white text-gray-700 border border-gray-200 rounded-xl text-base font-semibold hover:border-emerald-300 hover:text-emerald-600 transition">
                            {{ $s('hero_cta_secondary_text') }}
                        </a>
                    </div>
                </div>

                {{-- Right: Stats card --}}
                <div class="mg-reveal" style="animation-delay:.2s">
                    <div class="bg-white rounded-3xl shadow-xl shadow-emerald-900/5 p-8 border border-gray-50">
                        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-6">{{ $s('hero_stats_title') }}</h3>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-4xl font-extrabold text-emerald-600">{{ $s('stat_goats', '500+') }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $s('stat_goats_label') }}</p>
                            </div>
                            <div>
                                <p class="text-4xl font-extrabold text-emerald-600">{{ $s('stat_breeds', '12+') }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $s('stat_breeds_label') }}</p>
                            </div>
                            <div>
                                <p class="text-4xl font-extrabold text-emerald-600">{{ $s('stat_investors', '50+') }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $s('stat_investors_label') }}</p>
                            </div>
                            <div>
                                <p class="text-4xl font-extrabold text-emerald-600">{{ $s('stat_years', '7+') }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $s('stat_years_label') }}</p>
                            </div>
                        </div>
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1">
                                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.799-2.034a1 1 0 00-1.176 0l-2.799 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.363-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.799-2.034a1 1 0 00-1.176 0l-2.799 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.363-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.799-2.034a1 1 0 00-1.176 0l-2.799 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.363-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.799-2.034a1 1 0 00-1.176 0l-2.799 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.363-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.799-2.034a1 1 0 00-1.176 0l-2.799 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.363-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </div>
                                <p class="text-sm text-gray-500">{{ $s('hero_trust_text') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== ABOUT THE COMPANY ==================== --}}
    <section id="about" class="py-12 sm:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                {{-- Left: Story --}}
                <div class="mg-reveal">
                    <span class="text-sm font-bold text-emerald-600 uppercase tracking-wider">{{ $s('about_badge') }}</span>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight">
                        {{ $s('about_title') }}
                    </h2>
                    <p class="mt-6 text-lg text-gray-500 leading-relaxed">
                        {{ $s('about_text_1') }}
                    </p>
                    <p class="mt-4 text-base text-gray-500 leading-relaxed">
                        {{ $s('about_text_2') }}
                    </p>

                    <div class="mt-8 space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $s('about_highlight_1_title') }}</h4>
                                <p class="text-sm text-gray-500">{{ $s('about_highlight_1_desc') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $s('about_highlight_2_title') }}</h4>
                                <p class="text-sm text-gray-500">{{ $s('about_highlight_2_desc') }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $s('about_highlight_3_title') }}</h4>
                                <p class="text-sm text-gray-500">{{ $s('about_highlight_3_desc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Highlight cards --}}
                <div class="mg-reveal" style="animation-delay:.15s">
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Featured highlight --}}
                        <div class="col-span-2 bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-6 shadow-lg flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9v-2a2 2 0 00-2-2H8a2 2 0 00-2 2v2h12z" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-white text-lg leading-tight">{{ $s('stat_goats', '500+') }} Goats in Our Care</h4>
                                <p class="text-sm text-emerald-100 mt-0.5">Healthy, well-documented livestock</p>
                            </div>
                        </div>
                        {{-- Card: Sustainable --}}
                        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            </div>
                            <h4 class="font-bold text-gray-900 text-sm">Sustainable</h4>
                            <p class="text-xs text-gray-500 mt-1">Eco-conscious farming practices</p>
                        </div>
                        {{-- Card: Certified --}}
                        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-3 text-2xl">✓</div>
                            <h4 class="font-bold text-gray-900 text-sm">Quality Assured</h4>
                            <p class="text-xs text-gray-500 mt-1">Health-checked &amp; vaccinated</p>
                        </div>
                        {{-- Card: Profitable --}}
                        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h4 class="font-bold text-gray-900 text-sm">Profitable</h4>
                            <p class="text-xs text-gray-500 mt-1">Strong returns for investors</p>
                        </div>
                        {{-- Card: Trusted --}}
                        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <h4 class="font-bold text-gray-900 text-sm">Trusted</h4>
                            <p class="text-xs text-gray-500 mt-1">50+ satisfied investors</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== OUR SERVICES ==================== --}}
    <section id="services" class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 mg-reveal">
                <span class="text-sm font-bold text-emerald-600 uppercase tracking-wider">{{ $s('services_section_badge') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900">
                    {{ $s('services_section_title') }}
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    {{ $s('services_section_subtitle') }}
                </p>
            </div>

            @if($services->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    @foreach($services as $service)
                        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100 mg-hover-lift mg-reveal" style="animation-delay:{{ ($loop->index % 6) * 0.12 }}s">
                            <div class="w-12 h-12 {{ $service->icon_classes['bg'] }} rounded-xl flex items-center justify-center mb-5">
                                <svg class="w-6 h-6 {{ $service->icon_classes['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    {!! $service->icon_svg ?? \App\Models\SiteService::$iconSvgs['emerald'] !!}
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $service->title }}</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">{{ $service->description }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-400">Our services will be listed here soon.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- ==================== OUR BREEDS ==================== --}}
    <section id="breeds" class="py-12 sm:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 mg-reveal">
                <span class="text-sm font-bold text-emerald-600 uppercase tracking-wider">Our Livestock</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900">
                    Premium Breeds We Raise
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    We carefully select and raise the finest goat breeds known for health, productivity, and value.
                </p>
            </div>

            @if($breeds->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($breeds as $breed)
                        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-2xl p-6 text-center mg-hover-lift mg-reveal" style="animation-delay:{{ ($loop->index % 4) * 0.1 }}s">
                            <div class="w-14 h-14 mx-auto mb-4 flex items-center justify-center">
                                <svg viewBox="0 0 48 48" class="w-12 h-12 text-emerald-600" fill="currentColor"><ellipse cx="24" cy="28" rx="14" ry="10"/><ellipse cx="38" cy="20" rx="7" ry="6"/><path d="M34 13 Q32 6 35 4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path d="M41 13 Q43 6 40 4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><circle cx="40" cy="19" r="1.5" fill="#fff"/><rect x="16" y="36" width="3" height="8" rx="1"/><rect x="22" y="36" width="3" height="8" rx="1"/><rect x="28" y="36" width="3" height="8" rx="1"/><rect x="34" y="36" width="3" height="8" rx="1"/></svg>
                            </div>
                            <h3 class="font-bold text-gray-900 mb-1">{{ $breed->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $breed->description ?: 'Premium breed raised with care at Mehran Goats.' }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-14 h-14 mx-auto mb-4 flex items-center justify-center">
                        <svg viewBox="0 0 48 48" class="w-12 h-12 text-emerald-600" fill="currentColor"><ellipse cx="24" cy="28" rx="14" ry="10"/><ellipse cx="38" cy="20" rx="7" ry="6"/><rect x="16" y="36" width="3" height="8" rx="1"/><rect x="22" y="36" width="3" height="8" rx="1"/><rect x="28" y="36" width="3" height="8" rx="1"/><rect x="34" y="36" width="3" height="8" rx="1"/></svg>
                    </div>
                    <p class="text-gray-400">Our breed catalog is being updated. Please check back soon.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- ==================== WHY CHOOSE US ==================== --}}
    <section id="why-us" class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16 mg-reveal">
                <span class="text-sm font-bold text-emerald-600 uppercase tracking-wider">{{ $s('why_us_section_badge') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900">
                    {{ $s('why_us_section_title') }}
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    {{ $s('why_us_section_subtitle') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                @php $whyUsIcons = [1 => 'bg-emerald-600', 2 => 'bg-blue-600', 3 => 'bg-amber-600', 4 => 'bg-teal-600']; @endphp
                @php $whyUsSvgs = [
                    1 => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
                    2 => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />',
                    3 => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />',
                    4 => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
                ]; @endphp
                @foreach ([1, 2, 3, 4] as $i)
                    <div class="flex items-start gap-5 mg-reveal" style="animation-delay:{{ ($i - 1) * 0.1 }}s">
                        <div class="w-14 h-14 rounded-2xl {{ $whyUsIcons[$i] }} text-white flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                {!! $whyUsSvgs[$i] !!}
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $s("why_us_{$i}_title") }}</h3>
                            <p class="text-sm text-gray-500 leading-relaxed">{{ $s("why_us_{$i}_desc") }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== STATS BAND ==================== --}}
    <section class="relative bg-emerald-600 py-14 overflow-hidden">
        <svg class="mg-emoji" style="bottom:6px;left:0;width:24px;opacity:.25" viewBox="0 0 48 48" fill="white"><ellipse cx="24" cy="28" rx="14" ry="10"/><ellipse cx="38" cy="20" rx="7" ry="6"/><rect x="16" y="36" width="3" height="8"/><rect x="22" y="36" width="3" height="8"/><rect x="28" y="36" width="3" height="8"/><rect x="34" y="36" width="3" height="8"/></svg>
        <svg class="mg-emoji" style="bottom:6px;right:20%;width:20px;opacity:.18" viewBox="0 0 48 48" fill="white"><ellipse cx="24" cy="28" rx="14" ry="10"/><ellipse cx="38" cy="20" rx="7" ry="6"/><rect x="16" y="36" width="3" height="8"/><rect x="22" y="36" width="3" height="8"/><rect x="28" y="36" width="3" height="8"/><rect x="34" y="36" width="3" height="8"/></svg>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div class="mg-reveal">
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">{{ $s('stats_band_goats', '500+') }}</p>
                    <p class="mt-1 text-sm text-emerald-100">{{ $s('stats_band_goats_label') }}</p>
                </div>
                <div class="mg-reveal" style="animation-delay:.1s">
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">{{ $s('stats_band_investors', '50+') }}</p>
                    <p class="mt-1 text-sm text-emerald-100">{{ $s('stats_band_investors_label') }}</p>
                </div>
                <div class="mg-reveal" style="animation-delay:.2s">
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">{{ $s('stats_band_breeds', '12+') }}</p>
                    <p class="mt-1 text-sm text-emerald-100">{{ $s('stats_band_breeds_label') }}</p>
                </div>
                <div class="mg-reveal" style="animation-delay:.3s">
                    <p class="text-3xl sm:text-4xl font-extrabold text-white">{{ $s('stats_band_years', '7+') }}</p>
                    <p class="mt-1 text-sm text-emerald-100">{{ $s('stats_band_years_label') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== CONTACT / CTA ==================== --}}
    <section id="contact" class="py-12 sm:py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mg-reveal">
                <span class="text-sm font-bold text-emerald-600 uppercase tracking-wider">{{ $s('contact_badge') }}</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-gray-900">
                    {{ $s('contact_title') }}
                </h2>
                <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">
                    {{ $s('contact_subtitle') }}
                </p>

                <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-2xl mx-auto">
                    <a href="mailto:{{ $s('contact_email') }}" class="flex flex-col items-center gap-2 p-6 rounded-2xl border border-gray-100 mg-hover-lift">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $s('contact_email_label') }}</span>
                        <span class="text-xs text-gray-500">{{ $s('contact_email') }}</span>
                    </a>
                    <a href="tel:{{ preg_replace('/[^+\d]/', '', $s('contact_phone')) }}" class="flex flex-col items-center gap-2 p-6 rounded-2xl border border-gray-100 mg-hover-lift">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $s('contact_phone_label') }}</span>
                        <span class="text-xs text-gray-500">{{ $s('contact_phone') }}</span>
                    </a>
                    <div class="flex flex-col items-center gap-2 p-6 rounded-2xl border border-gray-100">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $s('contact_location_label') }}</span>
                        <span class="text-xs text-gray-500">{{ $s('contact_location') }}</span>
                    </div>
                </div>

                <div class="mt-10">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-8 py-3.5 bg-emerald-600 text-white rounded-xl text-base font-semibold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/20">
                            Go to Dashboard →
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-3.5 bg-emerald-600 text-white rounded-xl text-base font-semibold hover:bg-emerald-700 transition shadow-lg shadow-emerald-600/20">
                            Log in to Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FOOTER ==================== --}}
    <footer class="bg-gray-900 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                {{-- Company info --}}
                <div>
                    <div class="flex items-center gap-2.5 mb-4">
                        @if ($customLogo)
                            <img src="{{ asset('storage/' . $customLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto flex-shrink-0 mg-float object-contain">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="h-9 flex-shrink-0 mg-float" fill="none">
                                <circle cx="32" cy="32" r="30" fill="#059669"/>
                                <g transform="translate(0, -2)">
                                    <path d="M32 16 C24 16 20 22 20 30 C20 36 22 40 24 42 L22 46 C22 47 23 48 24 47 L26 45 C28 46 30 46.5 32 46.5 C34 46.5 36 46 38 45 L40 47 C41 48 42 47 42 46 L40 42 C42 40 44 36 44 30 C44 22 40 16 32 16 Z" fill="#ffffff"/>
                                    <ellipse cx="27" cy="30" rx="2" ry="2.5" fill="#059669"/>
                                    <ellipse cx="37" cy="30" rx="2" ry="2.5" fill="#059669"/>
                                    <path d="M23 18 C19 14 16 12 14 14 C15 17 18 19 21 20 Z" fill="#fbbf24"/>
                                    <path d="M41 18 C45 14 48 12 50 14 C49 17 46 19 43 20 Z" fill="#fbbf24"/>
                                </g>
                            </svg>
                        @endif
                        <div class="flex flex-col leading-none">
                            <span class="text-sm font-extrabold text-white tracking-tight">{{ $siteName }}</span>
                            <span class="text-[9px] font-medium text-gray-500 tracking-wider uppercase">{{ $siteTagline }}</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed max-w-xs">
                        {{ $s('footer_about') }}
                    </p>
                </div>

                {{-- Quick links --}}
                <div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Company</h4>
                    <ul class="space-y-3">
                        <li><a href="#about" class="text-sm text-gray-400 hover:text-emerald-400 transition">About Us</a></li>
                        <li><a href="#services" class="text-sm text-gray-400 hover:text-emerald-400 transition">Our Services</a></li>
                        <li><a href="#breeds" class="text-sm text-gray-400 hover:text-emerald-400 transition">Our Breeds</a></li>
                        <li><a href="#why-us" class="text-sm text-gray-400 hover:text-emerald-400 transition">Why Choose Us</a></li>
                    </ul>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Get in Touch</h4>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-2 text-sm text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            {{ $s('contact_email') }}
                        </li>
                        <li class="flex items-center gap-2 text-sm text-gray-400">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            {{ $s('contact_phone') }}
                        </li>
                        @auth
                            <li><a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm text-emerald-400 hover:text-emerald-300 transition font-medium">→ Investor Dashboard</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm text-emerald-400 hover:text-emerald-300 transition font-medium">→ Log in</a></li>
                        @endauth
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-500">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                <p class="text-xs text-gray-600">Premium Goat Farming &amp; Livestock Company</p>
            </div>
        </div>
    </footer>

    {{-- Scroll-reveal + smooth scroll + nav shadow --}}
    <script>
        (function () {
            // Scroll reveal
            var reveals = document.querySelectorAll('.mg-reveal');
            var reduce  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (reduce || !('IntersectionObserver' in window)) {
                reveals.forEach(function (el) { el.classList.add('is-visible'); });
            } else {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });
                reveals.forEach(function (el) { io.observe(el); });
            }

            // Nav shadow on scroll
            var nav = document.getElementById('mg-nav');
            if (nav) {
                window.addEventListener('scroll', function () {
                    if (window.scrollY > 10) {
                        nav.classList.add('shadow-md');
                    } else {
                        nav.classList.remove('shadow-md');
                    }
                }, { passive: true });
            }

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
                anchor.addEventListener('click', function (e) {
                    var target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        })();
    </script>

</body>
</html>
