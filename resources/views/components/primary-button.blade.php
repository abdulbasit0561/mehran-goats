<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-[#1b3a2b] border border-transparent rounded-xl font-semibold text-xs text-white tracking-wide shadow-sm hover:bg-[#244736] focus:bg-[#244736] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-emerald-600/40 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
