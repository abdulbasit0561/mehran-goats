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
        <meta name="theme-color" content="#059669" />
        <meta name="mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
        <meta name="apple-mobile-web-app-title" content="Mehran Goats" />
        <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <x-pwa-install-banner />

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="mb-2">
                <a href="/" class="flex flex-col items-center gap-3">
                    <x-logo size="h-14" :showText="false" />
                    <div class="text-center">
                        <span class="text-2xl font-extrabold text-gray-800 tracking-tight">Mehran Goats</span>
                        <p class="text-xs font-medium text-gray-400 tracking-wider uppercase">Farm Management</p>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
