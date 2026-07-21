<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
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
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" content="Mehran Goats" />
        <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <x-pwa-install-banner />
        <x-offline-status />

        @php
            // Determine page title + subtitle for the mobile app bar
            $pageTitle = $title ?? null;
            $pageSubtitle = $subtitle ?? null;
            if (!$pageTitle) {
                $routeName = request()->route()?->getName() ?? '';
                $titles = [
                    'dashboard' => 'Dashboard',
                    'goats.index' => 'Livestock',
                    'goats.create' => 'Add Goat',
                    'breeding-records.create' => 'Add Breeding Record',
                    'breeding-records.edit' => 'Edit Breeding Record',
                    'breeding-records.kidding' => 'Record Kidding',
                    'goats.edit' => 'Edit Goat',
                    'goats.show' => 'Goat Details',
                    'breeding.index' => 'Breeding & Pregnancy',
                    'caretakers.index' => 'Caretakers',
                    'caretakers.create' => 'Add Caretaker',
                    'caretakers.show' => 'Caretaker Details',
                    'caretakers.edit' => 'Edit Caretaker',
                    'investors.index' => 'Investors',
                    'investors.create' => 'Add Investor',
                    'investors.show' => 'Investor Details',
                    'investors.edit' => 'Edit Investor',
                    'transactions.index' => 'Expenses & Income',
                    'transactions.create' => 'Add Transaction',
                    'transactions.edit' => 'Edit Transaction',
                    'budget.index' => 'Budget Planner',
                    'budget.create' => 'Add Budget Row',
                    'budget.edit' => 'Edit Budget Row',
                    'breeds.index' => 'Breeds',
                    'breeds.create' => 'Add Breed',
                    'breeds.edit' => 'Edit Breed',
                    'reports.profit-loss' => 'Reports',
                    'instructions' => 'Instructions',
                    'profile.edit' => 'Profile',
                    'activity-logs.index' => 'Activity Logs',
                    'activity-logs.show' => 'Activity Detail',
                ];
                foreach ($titles as $pattern => $label) {
                    if (str_starts_with($routeName, explode('.', $pattern)[0])) {
                        $pageTitle = $label;
                        break;
                    }
                }
                $pageTitle ??= config('app.name');
            }

            // Determine context-specific "Add" button for the mobile header
            $mobileAddRoute = null;
            $mobileAddLabel = null;
            if (isset($headerAction)) {
                $mobileAddRoute = $headerAction['route'] ?? null;
                $mobileAddLabel = $headerAction['label'] ?? null;
            } else {
                $routeName = request()->route()?->getName() ?? '';
                if ($routeName === 'goats.index') { $mobileAddRoute = route('goats.create'); $mobileAddLabel = 'Goat'; }
                elseif ($routeName === 'investors.index') { $mobileAddRoute = route('investors.create'); $mobileAddLabel = 'Investor'; }
                elseif ($routeName === 'transactions.index') { $mobileAddRoute = route('transactions.create'); $mobileAddLabel = 'Transaction'; }
                elseif ($routeName === 'breeds.index') { $mobileAddRoute = route('breeds.create'); $mobileAddLabel = 'Breed'; }
                elseif ($routeName === 'budget.index') { $mobileAddRoute = route('budget.create'); $mobileAddLabel = 'Budget'; }
            }

            // Default subtitle when none provided — show app tagline on dashboard
            if (!$pageSubtitle && request()->routeIs('dashboard')) {
                $pageSubtitle = 'Farm Management';
            }
        @endphp

        <div class="min-h-screen bg-gray-50">
            {{-- Desktop navigation (hidden on mobile via its own sm: classes) --}}
            @include('layouts.navigation')

            {{-- ================================================================ --}}
            {{-- MOBILE APP BAR (dark-green branded header)                        --}}
            {{-- ================================================================ --}}
            <header class="mobile-app-bar">
                <div class="mg-bar-left">
                    <div class="mg-logo-circle">
                        {{-- Goat icon --}}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 010-4h14a2 2 0 010 4M5 8v8a2 2 0 002 2h10a2 2 0 002-2V8M9 12h6"/>
                        </svg>
                    </div>
                    <div class="mg-titles">
                        <span class="mg-title">{{ $pageTitle }}</span>
                        @if ($pageSubtitle)
                            <span class="mg-subtitle">{{ $pageSubtitle }}</span>
                        @endif
                    </div>
                </div>
                <div class="mg-bar-right">
                    @if ($mobileAddRoute)
                        <a href="{{ $mobileAddRoute }}" class="mg-add-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            {{ $mobileAddLabel }}
                        </a>
                    @endif
                </div>
            </header>

            {{-- Page header (desktop only — hidden on mobile via mobile-page-header class) --}}
            @isset($header)
                <header class="mobile-page-header bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- Page Content --}}
            <main class="page-content">
                {{ $slot }}
            </main>
        </div>

        {{-- ================================================================ --}}
        {{-- MOBILE BOTTOM TAB BAR (5 tabs with SVG icons)                     --}}
        {{-- ================================================================ --}}
        <nav class="bottom-tab-bar" x-data>
            {{-- Home / Dashboard --}}
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Home</span>
            </a>

            {{-- Investors --}}
            <a href="{{ route('investors.index') }}" class="{{ request()->routeIs('investors.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Investors</span>
            </a>

            {{-- Livestock --}}
            <a href="{{ route('goats.index') }}" class="{{ request()->routeIs('goats.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v8a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                <span>Livestock</span>
            </a>

            {{-- Reports --}}
            <a href="{{ route('reports.profit-loss') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Reports</span>
            </a>

            {{-- More (opens slide-up sheet via Alpine store) --}}
            <a href="#" @click.prevent="$store.ui.openMore()" class="{{ request()->routeIs('transactions.*') || request()->routeIs('breeds.*') || request()->routeIs('caretakers.*') || request()->routeIs('breeding.*') || request()->routeIs('goats.breeding-records.*') || request()->routeIs('budget.*') || request()->routeIs('instructions') || request()->routeIs('profile.*') || request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>More</span>
                {{-- Offline pending count badge --}}
                <template x-if="$store.offline && $store.offline.pendingCount > 0">
                    <span class="offline-badge"
                          x-text="$store.offline.pendingCount"
                          @click.prevent="$store.offline.syncNow()"></span>
                </template>
            </a>
        </nav>

        {{-- ================================================================ --}}
        {{-- MOBILE "MORE" SLIDE-UP SHEET                                       --}}
        {{-- .mobile-only wrapper hides on desktop; x-show handles open/close  --}}
        {{-- ================================================================ --}}
        <div class="mobile-only">
        <div x-data
             @keydown.escape.window="$store.ui.closeMore()">
            {{-- Backdrop --}}
            <div x-show="$store.ui.moreOpen"
                 @click="$store.ui.closeMore()"
                 x-transition.opacity
                 class="fixed inset-0 bg-black/50 z-[55]"
                 style="display: none;">
            </div>

            {{-- Sheet --}}
            <div x-show="$store.ui.moreOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full"
                 class="mobile-more-sheet fixed bottom-0 left-0 right-0 z-[56]"
                 style="display: none; padding-bottom: env(safe-area-inset-bottom);">
                <div class="p-4">
                    <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>

                    <div class="flex items-center gap-3 mb-4 px-2 pb-3 border-b border-gray-100">
                        <div class="mg-avatar">
                            {{ strtoupper(Auth::user()->name[0] ?? '?') }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        {{-- Offline Sync indicator + sync button --}}
                        <a href="#" class="more-grid-item" @click.prevent="$store.offline && $store.offline.syncNow(); $store.ui.closeMore()" x-show="$store.offline && $store.offline.pendingCount > 0" style="display:none;">
                            <span class="more-icon relative">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span class="absolute -top-1 -right-1 bg-amber-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1" x-text="$store.offline && $store.offline.pendingCount"></span>
                            </span>
                            <span class="more-label">Sync Now</span>
                        </a>
                        <a href="{{ route('caretakers.index') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </span>
                            <span class="more-label">Caretakers</span>
                        </a>
                        <a href="{{ route('breeding.index') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            </span>
                            <span class="more-label">Breeding</span>
                        </a>
                        <a href="{{ route('transactions.index') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </span>
                            <span class="more-label">Expenses</span>
                        </a>
                        <a href="{{ route('breeds.index') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </span>
                            <span class="more-label">Breeds</span>
                        </a>
                        <a href="{{ route('budget.index') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span class="more-label">Budget</span>
                        </a>
                        <a href="{{ route('instructions') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <span class="more-label">Guide</span>
                        </a>
                        @if (Auth::user()?->isSuperAdmin())
                            <a href="{{ route('activity-logs.index') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                                <span class="more-icon">
                                    <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </span>
                                <span class="more-label">Logs</span>
                            </a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <span class="more-label">Profile</span>
                        </a>
                        <a href="{{ route('investors.index') }}" class="more-grid-item" @click="$store.ui.closeMore()">
                            <span class="more-icon">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </span>
                            <span class="more-label">Investors</span>
                        </a>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-red-50 text-red-600 text-sm font-semibold active:bg-red-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </div>{{-- /mobile-only --}}

        @stack('scripts')
    </body>
</html>
