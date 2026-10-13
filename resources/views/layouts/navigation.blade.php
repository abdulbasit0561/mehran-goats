{{-- Desktop sidebar. Mobile uses the app bar + bottom tabs in the layout. --}}
<aside class="admin-sidebar" aria-label="Admin">
    <div class="admin-brand">
        <a href="{{ route('dashboard') }}" class="block">
            <x-logo size="h-9" textClass="text-base" />
        </a>
    </div>

    <nav class="admin-nav" x-data="{
        open: { overview: false, livestock: false, people: false, finance: false, reports: false, workspace: false },
        init() {
            try {
                const saved = JSON.parse(localStorage.getItem('mg-nav-groups-v2') || 'null');
                if (saved && typeof saved === 'object') {
                    Object.keys(this.open).forEach((key) => {
                        if (typeof saved[key] === 'boolean') this.open[key] = saved[key];
                    });
                }
            } catch (e) {}
        },
        toggle(key) {
            this.open[key] = !this.open[key];
            localStorage.setItem('mg-nav-groups-v2', JSON.stringify(this.open));
        },
        isOpen(key) {
            return !!this.open[key];
        }
    }">
        <section class="side-group is-collapsed" :class="{ 'is-collapsed': !isOpen('overview') }">
        <button type="button" class="side-group-btn" @click="toggle('overview')" :aria-expanded="isOpen('overview')">
            <span>Overview</span>
            <svg class="side-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
        <div class="side-group-panel" :inert="!isOpen('overview')">
        <div class="side-group-panel-inner">
        <a href="{{ route('dashboard') }}" class="side-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('agent.index') }}" class="side-link {{ request()->routeIs('agent.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Farm agent
        </a>
        <a href="{{ route('farm.ai-insights.index') }}" class="side-link {{ request()->routeIs('farm.ai-insights.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            AI Insights
        </a>
        </div>
        </div>
        </section>

        <section class="side-group is-collapsed" :class="{ 'is-collapsed': !isOpen('livestock') }">
        <button type="button" class="side-group-btn" @click="toggle('livestock')" :aria-expanded="isOpen('livestock')">
            <span>Livestock</span>
            <svg class="side-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
        <div class="side-group-panel" :inert="!isOpen('livestock')">
        <div class="side-group-panel-inner">
        <a href="{{ route('goats.index') }}" class="side-link {{ request()->routeIs('goats.*') && ! request()->routeIs('goats.breeding-records.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v8a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            Herd
        </a>
        <a href="{{ route('breeds.index') }}" class="side-link {{ request()->routeIs('breeds.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Breeds
        </a>
        <a href="{{ route('breeding.index') }}" class="side-link {{ request()->routeIs('breeding.*') || request()->routeIs('goats.breeding-records.*') || request()->routeIs('breeding-records.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            Breeding
        </a>
        </div>
        </div>
        </section>

        <section class="side-group is-collapsed" :class="{ 'is-collapsed': !isOpen('people') }">
        <button type="button" class="side-group-btn" @click="toggle('people')" :aria-expanded="isOpen('people')">
            <span>People</span>
            <svg class="side-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
        <div class="side-group-panel" :inert="!isOpen('people')">
        <div class="side-group-panel-inner">
        <a href="{{ route('investors.index') }}" class="side-link {{ request()->routeIs('investors.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Investors
        </a>
        <a href="{{ route('caretakers.index') }}" class="side-link {{ request()->routeIs('caretakers.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Caretakers
        </a>
        <a href="{{ route('customers.index') }}" class="side-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Buyers
        </a>
        </div>
        </div>
        </section>

        <section class="side-group is-collapsed" :class="{ 'is-collapsed': !isOpen('finance') }">
        <button type="button" class="side-group-btn" @click="toggle('finance')" :aria-expanded="isOpen('finance')">
            <span>Finance</span>
            <svg class="side-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
        <div class="side-group-panel" :inert="!isOpen('finance')">
        <div class="side-group-panel-inner">
        <a href="{{ route('transactions.index') }}" class="side-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Expenses
        </a>
        <a href="{{ route('sales.index') }}" class="side-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l2 2 4-4M7 4h10a2 2 0 012 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 012-2z"/></svg>
            Sales
        </a>
        <a href="{{ route('exchanges.index') }}" class="side-link {{ request()->routeIs('exchanges.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Exchanges
        </a>
        <a href="{{ route('budget.index') }}" class="side-link {{ request()->routeIs('budget.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Budget
        </a>
        </div>
        </div>
        </section>

        <section class="side-group is-collapsed" :class="{ 'is-collapsed': !isOpen('reports') }">
        <button type="button" class="side-group-btn" @click="toggle('reports')" :aria-expanded="isOpen('reports')">
            <span>Reports</span>
            <svg class="side-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
        <div class="side-group-panel" :inert="!isOpen('reports')">
        <div class="side-group-panel-inner">
        <a href="{{ route('reports.profit-loss') }}" class="side-link {{ request()->routeIs('reports.profit-loss*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Profit &amp; Loss
        </a>
        <a href="{{ route('reports.sales') }}" class="side-link {{ request()->routeIs('reports.sales*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v16H4z"/></svg>
            Sales Report
        </a>
        <a href="{{ route('reports.yearly-clearance.index') }}" class="side-link {{ request()->routeIs('reports.yearly-clearance*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Yearly Clearance
        </a>
        </div>
        </div>
        </section>

        <section class="side-group is-collapsed" :class="{ 'is-collapsed': !isOpen('workspace') }">
        <button type="button" class="side-group-btn" @click="toggle('workspace')" :aria-expanded="isOpen('workspace')">
            <span>Workspace</span>
            <svg class="side-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
        <div class="side-group-panel" :inert="!isOpen('workspace')">
        <div class="side-group-panel-inner">
        <a href="{{ route('cms.index') }}" class="side-link {{ request()->routeIs('cms.*') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            Website
        </a>
        <a href="{{ route('instructions') }}" class="side-link {{ request()->routeIs('instructions') ? 'active' : '' }}">
            <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Guide
        </a>
        @if (Auth::user()?->isSuperAdmin())
            <a href="{{ route('activity-logs.index') }}" class="side-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <svg class="side-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Activity Logs
            </a>
        @endif
        </div>
        </div>
        </section>
    </nav>

    <div class="admin-side-foot">
        <a href="{{ route('profile.edit') }}" class="side-user {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span class="mg-avatar">{{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
            <span class="min-w-0">
                <span class="side-user-name">{{ Auth::user()->name }}</span>
                <span class="side-user-mail">{{ Auth::user()->email }}</span>
            </span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="side-logout">Log out</button>
        </form>
    </div>
</aside>
