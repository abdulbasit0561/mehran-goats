@props(['size' => 'h-10', 'showText' => true, 'textClass' => 'text-lg', 'variant' => 'dark'])

<div class="flex items-center gap-2.5">
    {{-- SVG Goat Emblem --}}
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" class="{{ $size }} flex-shrink-0" fill="none">
        {{-- Circular badge background --}}
        <circle cx="32" cy="32" r="30" fill="url(#mg-grad)" />
        <circle cx="32" cy="32" r="28" stroke="#ffffff" stroke-width="1" opacity="0.3" />

        {{-- Goat head silhouette --}}
        <g transform="translate(0, -2)">
            {{-- Face --}}
            <path d="M32 16
                     C24 16 20 22 20 30
                     C20 36 22 40 24 42
                     L22 46 C22 47 23 48 24 47
                     L26 45 C28 46 30 46.5 32 46.5
                     C34 46.5 36 46 38 45
                     L40 47 C41 48 42 47 42 46
                     L40 42 C42 40 44 36 44 30
                     C44 22 40 16 32 16 Z"
                  fill="#ffffff"/>

            {{-- Ears --}}
            <path d="M20 28 C16 27 13 28 12 30 C13 32 16 33 20 32 Z" fill="#ffffff" opacity="0.9"/>
            <path d="M44 28 C48 27 51 28 52 30 C51 32 48 33 44 32 Z" fill="#ffffff" opacity="0.9"/>

            {{-- Eyes --}}
            <ellipse cx="27" cy="30" rx="2" ry="2.5" fill="#059669"/>
            <ellipse cx="37" cy="30" rx="2" ry="2.5" fill="#059669"/>

            {{-- Nostrils --}}
            <circle cx="29.5" cy="40" r="0.8" fill="#059669"/>
            <circle cx="34.5" cy="40" r="0.8" fill="#059669"/>

            {{-- Horns --}}
            <path d="M23 18 C19 14 16 12 14 14 C15 17 18 19 21 20 Z" fill="#fef3c7"/>
            <path d="M41 18 C45 14 48 12 50 14 C49 17 46 19 43 20 Z" fill="#fef3c7"/>
            <path d="M22 17 C19 14 17 13 15.5 14.5 C16.5 16 18.5 17.5 20.5 18 Z" fill="#fbbf24"/>
            <path d="M42 17 C45 14 47 13 48.5 14.5 C47.5 16 45.5 17.5 43.5 18 Z" fill="#fbbf24"/>

            {{-- Beard --}}
            <path d="M30 45 L31 50 L32 52 L33 50 L34 45 Z" fill="#ffffff" opacity="0.9"/>
        </g>

        <defs>
            <linearGradient id="mg-grad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#10b981"/>
                <stop offset="100%" stop-color="#059669"/>
            </linearGradient>
        </defs>
    </svg>

    @if($showText)
        <div class="flex flex-col leading-none">
            <span class="{{ $textClass }} font-extrabold tracking-tight {{ $variant === 'light' ? 'text-white' : 'text-gray-800' }}">Mehran Goats</span>
            <span class="text-[10px] font-medium tracking-wider uppercase {{ $variant === 'light' ? 'text-white/55' : 'text-gray-400' }}">Farm Management</span>
        </div>
    @endif
</div>
