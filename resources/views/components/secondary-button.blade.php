<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 bg-white border border-stone-200 rounded-xl font-semibold text-xs text-stone-700 tracking-wide shadow-sm hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-emerald-600/30 focus:ring-offset-2 disabled:opacity-25 active:scale-[0.98] transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
