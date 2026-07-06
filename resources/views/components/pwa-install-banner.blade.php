@props([])

{{-- PWA Install Banner — shows when Chrome fires beforeinstallprompt --}}
<div x-data="{
        show: false,
        dismissedAt: parseInt(localStorage.getItem('pwa_install_dismissed') || '0'),
        init() {
            const showIfEligible = () => {
                const dismissedRecently = (Date.now() - this.dismissedAt) < 7 * 24 * 60 * 60 * 1000;
                this.show = window.PwaInstall?.canInstall === true
                         && !window.PwaInstall?.installed
                         && !dismissedRecently;
            };
            // React to the browser firing beforeinstallprompt
            window.addEventListener('pwa-install-available', () => showIfEligible());
            window.addEventListener('pwa-installed', () => { this.show = false; });
            // Also poll briefly in case the event already fired before Alpine init
            showIfEligible();
            setTimeout(() => showIfEligible(), 2000);
            setTimeout(() => showIfEligible(), 5000);
        },
        install() {
            window.triggerPwaInstall?.();
            this.show = false;
        },
        dismiss() {
            window.dismissPwaInstall?.();
            this.show = false;
            try { localStorage.setItem('pwa_install_dismissed', Date.now().toString()); } catch(e) {}
        }
     }"
     x-show="show"
     x-transition
     x-cloak
     class="fixed bottom-4 inset-x-4 sm:left-auto sm:right-4 sm:max-w-sm z-50">
    <div class="bg-emerald-600 text-white rounded-xl shadow-2xl p-4 flex items-center gap-3">
        <img src="{{ asset('icons/icon-192.png') }}" alt="Mehran Goats" class="w-12 h-12 rounded-lg flex-shrink-0" />
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm">Install Mehran Goats</p>
            <p class="text-xs text-emerald-100">Add to your home screen for quick access</p>
        </div>
        <div class="flex flex-col gap-1 flex-shrink-0">
            <button @click="install()" class="bg-white text-emerald-700 font-semibold text-xs px-3 py-1.5 rounded-lg hover:bg-emerald-50 transition">
                Install
            </button>
            <button @click="dismiss()" class="text-emerald-100 hover:text-white text-xs px-2 py-0.5 transition">
                Not now
            </button>
        </div>
    </div>
</div>
