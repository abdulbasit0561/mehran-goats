<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}" />
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icons/favicon-16.png') }}" />

        <!-- PWA -->
        <link rel="manifest" href="{{ asset('manifest.json') }}" />
        <meta name="theme-color" content="#1b3a2b" />
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
        <meta name="apple-mobile-web-app-title" content="Mehran Goats" />
        <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-stone-800">
        <x-pwa-install-banner />

        <div class="auth-shell">
            <div class="auth-card">
                <a href="/" class="auth-brand">
                    <x-logo size="h-12" textClass="text-xl" />
                </a>

                <div class="auth-panel">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
