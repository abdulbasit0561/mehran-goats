{{-- Offline-first status banner --}}
{{-- Shows online/offline state, pending count, sync button --}}
{{-- Hidden on desktop (mobile-only — but visible on all screens if offline) --}}

<div x-data
     x-cloak
     x-show="$store.offline && (!$store.offline.isOnline || $store.offline.pendingCount > 0 || $store.offline.syncing)"
     x-transition
     class="offline-status-bar"
     style="display: none;">

    {{-- OFFLINE STATE --}}
    <template x-if="!$store.offline.isOnline && !$store.offline.syncing">
        <div class="flex items-center gap-2 w-full">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium">Offline</span>
            <span class="text-xs opacity-80" x-show="$store.offline.pendingCount > 0"
                  x-text="`(${$store.offline.pendingCount} pending)`"></span>
        </div>
    </template>

    {{-- SYNCING STATE --}}
    <template x-if="$store.offline.syncing">
        <div class="flex items-center gap-2 w-full justify-center">
            <svg class="animate-spin w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span class="text-sm font-medium">Syncing <span x-text="$store.offline.pendingCount"></span> item(s)…</span>
        </div>
    </template>

    {{-- ONLINE WITH PENDING ITEMS --}}
    <template x-if="$store.offline.isOnline && !$store.offline.syncing && $store.offline.pendingCount > 0">
        <div class="flex items-center gap-2 w-full justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span class="text-sm font-medium" x-text="$store.offline.pendingCount + ' item(s) pending'"></span>
            </div>
            <button @click="$store.offline.syncNow()"
                    class="text-xs font-bold uppercase tracking-wide bg-white/20 hover:bg-white/30 rounded-lg px-3 py-1 active:scale-95 transition">
                Sync Now
            </button>
        </div>
    </template>

    {{-- ERROR MESSAGE --}}
    <template x-if="$store.offline.lastError">
        <div class="text-xs opacity-90 mt-1" x-text="$store.offline.lastError"></div>
    </template>
</div>
