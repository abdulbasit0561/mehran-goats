<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🌐 Website CMS</h2>
    </x-slot>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Website CMS</h1>
        <p class="mt-1 text-sm text-gray-500">Manage all content shown on the public landing page, including SEO settings.</p>
    </div>

    {{-- Success flash --}}
    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto">
            <a href="{{ route('cms.index', ['tab' => 'branding']) }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 whitespace-nowrap transition {{ request('tab', 'branding') === 'branding' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                🎨 Branding
            </a>
            <a href="{{ route('cms.index', ['tab' => 'general']) }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 whitespace-nowrap transition {{ request('tab') === 'general' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                General Content
            </a>
            <a href="{{ route('cms.index', ['tab' => 'seo']) }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 whitespace-nowrap transition {{ request('tab') === 'seo' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                SEO Settings
            </a>
            <a href="{{ route('cms.index', ['tab' => 'services']) }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 whitespace-nowrap transition {{ request('tab') === 'services' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Service Cards
            </a>
        </nav>
    </div>

    @php $tab = request('tab', 'branding'); @endphp

    {{-- ==================== BRANDING TAB ==================== --}}
    @if ($tab === 'branding')
        <form method="POST" action="{{ route('cms.update-branding') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-6">

                {{-- Page title & theme --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Page Title &amp; Theme</h3>
                    <p class="text-sm text-gray-400 mb-4">Set the browser tab title and the brand colour used across the site.</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Browser Tab Title</label>
                            <input type="text" name="site_page_title" value="{{ $settings['site_page_title'] ?? '' }}" placeholder="Mehran Goats"
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p class="mt-1 text-xs text-gray-400">Shown in the browser tab. If empty, the SEO Title from the SEO tab is used.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Theme Colour <span class="text-gray-400 font-normal">(hex)</span></label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="site_theme_color" value="{{ $settings['site_theme_color'] ?? '#059669' }}"
                                       class="w-12 h-10 rounded-lg border border-gray-200 cursor-pointer">
                                <input type="text" name="site_theme_color_text" value="{{ $settings['site_theme_color'] ?? '#059669' }}" id="theme_color_text"
                                       class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" readonly>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Used for PWA theme-color and mobile browser UI.</p>
                        </div>
                    </div>
                </div>

                {{-- Logo --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Site Logo</h3>
                    <p class="text-sm text-gray-400 mb-4">Upload a logo to replace the default goat icon in the navigation bar and footer.</p>
                    <div class="flex flex-col sm:flex-row items-start gap-6">
                        <div class="flex-shrink-0">
                            <p class="text-xs text-gray-500 mb-2">Current logo:</p>
                            <div class="w-32 h-32 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 overflow-hidden">
                                @if (!empty($settings['site_logo']))
                                    <img src="{{ asset('storage/' . $settings['site_logo']) }}" alt="Logo" class="max-w-full max-h-full object-contain p-2">
                                @else
                                    <span class="text-xs text-gray-400 text-center px-2">Default goat icon</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload New Logo</label>
                            <input type="file" name="site_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                   class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                            <p class="mt-1 text-xs text-gray-400">PNG, JPG, SVG, or WebP. Max 1MB. Recommended size: 200×200px.</p>
                            @if (!empty($settings['site_logo']))
                                <label class="mt-3 flex items-center gap-2 text-sm text-red-600">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Remove custom logo (revert to default goat icon)
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Favicon --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Favicon</h3>
                    <p class="text-sm text-gray-400 mb-4">Upload a favicon to replace the default icon shown in the browser tab.</p>
                    <div class="flex flex-col sm:flex-row items-start gap-6">
                        <div class="flex-shrink-0">
                            <p class="text-xs text-gray-500 mb-2">Current favicon:</p>
                            <div class="w-16 h-16 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 overflow-hidden">
                                @if (!empty($settings['site_favicon']))
                                    <img src="{{ asset('storage/' . $settings['site_favicon']) }}" alt="Favicon" class="max-w-full max-h-full object-contain p-1">
                                @else
                                    <span class="text-xs text-gray-400 text-center px-1">Default</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload New Favicon</label>
                            <input type="file" name="site_favicon" accept="image/png,image/svg+xml,image/x-icon"
                                   class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                            <p class="mt-1 text-xs text-gray-400">PNG, ICO, or SVG. Max 512KB. Recommended: 32×32px or 64×64px.</p>
                            @if (!empty($settings['site_favicon']))
                                <label class="mt-3 flex items-center gap-2 text-sm text-red-600">
                                    <input type="checkbox" name="remove_favicon" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Remove custom favicon
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Apple Touch Icon --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Apple Touch Icon</h3>
                    <p class="text-sm text-gray-400 mb-4">Icon used when users add the site to their iPhone/iPad home screen.</p>
                    <div class="flex flex-col sm:flex-row items-start gap-6">
                        <div class="flex-shrink-0">
                            <p class="text-xs text-gray-500 mb-2">Current icon:</p>
                            <div class="w-16 h-16 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 overflow-hidden">
                                @if (!empty($settings['site_apple_icon']))
                                    <img src="{{ asset('storage/' . $settings['site_apple_icon']) }}" alt="Apple Touch Icon" class="max-w-full max-h-full object-contain p-1 rounded-lg">
                                @else
                                    <span class="text-xs text-gray-400 text-center px-1">Default</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload New Apple Touch Icon</label>
                            <input type="file" name="site_apple_icon" accept="image/png"
                                   class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                            <p class="mt-1 text-xs text-gray-400">PNG only. Max 512KB. Recommended: 180×180px.</p>
                            @if (!empty($settings['site_apple_icon']))
                                <label class="mt-3 flex items-center gap-2 text-sm text-red-600">
                                    <input type="checkbox" name="remove_apple_icon" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    Remove custom Apple touch icon
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700 transition">
                        Save Branding
                    </button>
                </div>
            </div>
        </form>

        <script>
            (function() {
                var colorInput = document.querySelector('input[type="color"][name="site_theme_color"]');
                var textDisplay = document.getElementById('theme_color_text');
                if (colorInput && textDisplay) {
                    colorInput.addEventListener('input', function() {
                        textDisplay.value = colorInput.value;
                    });
                }
            })();
        </script>

    {{-- ==================== GENERAL TAB ==================== --}}
    @elseif ($tab === 'general')
        <form method="POST" action="{{ route('cms.update-general') }}">
            @csrf
            <div class="space-y-8">

                {{-- Site Identity --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Site Identity</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                            <input type="text" name="site_name" value="{{ $settings['site_name'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site Tagline</label>
                            <input type="text" name="site_tagline" value="{{ $settings['site_tagline'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                {{-- Farm Video Banner --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Farm Video Banner</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Video Title</label>
                            <input type="text" name="farm_video_title" value="{{ $settings['farm_video_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Video Subtitle</label>
                            <textarea name="farm_video_subtitle" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['farm_video_subtitle'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Hero Section --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Hero Section</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge Text</label>
                            <input type="text" name="hero_badge" value="{{ $settings['hero_badge'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" name="hero_title" value="{{ $settings['hero_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                            <textarea name="hero_subtitle" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['hero_subtitle'] ?? '' }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary CTA Text</label>
                                <input type="text" name="hero_cta_primary_text" value="{{ $settings['hero_cta_primary_text'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Secondary CTA Text</label>
                                <input type="text" name="hero_cta_secondary_text" value="{{ $settings['hero_cta_secondary_text'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stats Card Title</label>
                                <input type="text" name="hero_stats_title" value="{{ $settings['hero_stats_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Trust Text</label>
                                <input type="text" name="hero_trust_text" value="{{ $settings['hero_trust_text'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats (hero card) --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Hero Stats Card</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ([['goats', 'Goats'], ['breeds', 'Breeds'], ['investors', 'Investors'], ['years', 'Years']] as $stat)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stat {{ ucfirst($stat[1]) }} Value</label>
                                <input type="text" name="stat_{{ $stat[0] }}" value="{{ $settings['stat_' . $stat[0]] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stat {{ ucfirst($stat[1]) }} Label</label>
                                <input type="text" name="stat_{{ $stat[0] }}_label" value="{{ $settings['stat_' . $stat[0] . '_label'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Stats Band --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Stats Band (Green Section)</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ([['goats', 'Goats'], ['investors', 'Investors'], ['breeds', 'Breeds'], ['years', 'Years']] as $stat)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Band {{ ucfirst($stat[1]) }} Value</label>
                                <input type="text" name="stats_band_{{ $stat[0] }}" value="{{ $settings['stats_band_' . $stat[0]] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Band {{ ucfirst($stat[1]) }} Label</label>
                                <input type="text" name="stats_band_{{ $stat[0] }}_label" value="{{ $settings['stats_band_' . $stat[0] . '_label'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- About Section --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">About Section</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge</label>
                            <input type="text" name="about_badge" value="{{ $settings['about_badge'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" name="about_title" value="{{ $settings['about_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Paragraph 1</label>
                            <textarea name="about_text_1" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['about_text_1'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Paragraph 2</label>
                            <textarea name="about_text_2" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['about_text_2'] ?? '' }}</textarea>
                        </div>
                        @foreach ([1, 2, 3] as $i)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Highlight {{ $i }} Title</label>
                                    <input type="text" name="about_highlight_{{ $i }}_title" value="{{ $settings['about_highlight_' . $i . '_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Highlight {{ $i }} Description</label>
                                    <input type="text" name="about_highlight_{{ $i }}_desc" value="{{ $settings['about_highlight_' . $i . '_desc'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Services Section Header --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Services Section Header</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge</label>
                            <input type="text" name="services_section_badge" value="{{ $settings['services_section_badge'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" name="services_section_title" value="{{ $settings['services_section_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                            <textarea name="services_section_subtitle" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['services_section_subtitle'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Why Us Section --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Why Us Section</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge</label>
                            <input type="text" name="why_us_section_badge" value="{{ $settings['why_us_section_badge'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" name="why_us_section_title" value="{{ $settings['why_us_section_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                            <textarea name="why_us_section_subtitle" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['why_us_section_subtitle'] ?? '' }}</textarea>
                        </div>
                        @foreach ([1, 2, 3, 4] as $i)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason {{ $i }} Title</label>
                                    <input type="text" name="why_us_{{ $i }}_title" value="{{ $settings['why_us_' . $i . '_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason {{ $i }} Description</label>
                                    <input type="text" name="why_us_{{ $i }}_desc" value="{{ $settings['why_us_' . $i . '_desc'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Contact Section --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Contact Section</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge</label>
                            <input type="text" name="contact_badge" value="{{ $settings['contact_badge'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                            <input type="text" name="contact_title" value="{{ $settings['contact_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                            <textarea name="contact_subtitle" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['contact_subtitle'] ?? '' }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Label</label>
                                <input type="text" name="contact_email_label" value="{{ $settings['contact_email_label'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Label</label>
                                <input type="text" name="contact_phone_label" value="{{ $settings['contact_phone_label'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location Label</label>
                                <input type="text" name="contact_location_label" value="{{ $settings['contact_location_label'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="text" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location Text</label>
                                <input type="text" name="contact_location" value="{{ $settings['contact_location'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Footer</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Footer About Text</label>
                        <textarea name="footer_about" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['footer_about'] ?? '' }}</textarea>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700 transition">
                        Save General Content
                    </button>
                </div>
            </div>
        </form>

    {{-- ==================== SEO TAB ==================== --}}
    @elseif ($tab === 'seo')
        <form method="POST" action="{{ route('cms.update-seo') }}">
            @csrf
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Basic SEO</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SEO Title <span class="text-gray-400 font-normal">(shown in browser tab & search results)</span></label>
                            <input type="text" name="seo_title" value="{{ $settings['seo_title'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <p class="mt-1 text-xs text-gray-400">Recommended: 50-60 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea name="seo_description" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $settings['seo_description'] ?? '' }}</textarea>
                            <p class="mt-1 text-xs text-gray-400">Recommended: 150-160 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords <span class="text-gray-400 font-normal">(comma-separated)</span></label>
                            <input type="text" name="seo_keywords" value="{{ $settings['seo_keywords'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Open Graph & Twitter</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL <span class="text-gray-400 font-normal">(leave empty for default)</span></label>
                            <input type="text" name="seo_og_image" value="{{ $settings['seo_og_image'] ?? '' }}" placeholder="/images/og-image.jpg" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">OG Type</label>
                                <select name="seo_og_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach (['website', 'article', 'product'] as $type)
                                        <option value="{{ $type }}" @selected(($settings['seo_og_type'] ?? 'website') === $type)>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Twitter Card Type</label>
                                <select name="seo_twitter_card" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach (['summary', 'summary_large_image', 'app', 'player'] as $card)
                                        <option value="{{ $card }}" @selected(($settings['seo_twitter_card'] ?? 'summary_large_image') === $card)>{{ ucfirst(str_replace('_', ' ', $card)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Robots & Canonical</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Robots Index</label>
                                <select name="seo_robots_index" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="index" @selected(($settings['seo_robots_index'] ?? 'index') === 'index')>Index (allow search engines)</option>
                                    <option value="noindex" @selected(($settings['seo_robots_index'] ?? '') === 'noindex')>No Index (block search engines)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Robots Follow</label>
                                <select name="seo_robots_follow" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="follow" @selected(($settings['seo_robots_follow'] ?? 'follow') === 'follow')>Follow links</option>
                                    <option value="nofollow" @selected(($settings['seo_robots_follow'] ?? '') === 'nofollow')>No Follow links</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Canonical URL <span class="text-gray-400 font-normal">(leave empty for auto)</span></label>
                            <input type="text" name="seo_canonical_url" value="{{ $settings['seo_canonical_url'] ?? '' }}" placeholder="https://example.com/" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Structured Data (JSON-LD)</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Schema Type</label>
                                <select name="seo_structured_data_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach (['Organization', 'LocalBusiness', 'WebSite', 'Farm'] as $type)
                                        <option value="{{ $type }}" @selected(($settings['seo_structured_data_type'] ?? 'Organization') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
                                <input type="text" name="seo_structured_data_name" value="{{ $settings['seo_structured_data_name'] ?? '' }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Website URL <span class="text-gray-400 font-normal">(leave empty for auto)</span></label>
                                <input type="text" name="seo_structured_data_url" value="{{ $settings['seo_structured_data_url'] ?? '' }}" placeholder="https://example.com" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
                                <input type="text" name="seo_structured_data_logo" value="{{ $settings['seo_structured_data_logo'] ?? '' }}" placeholder="/images/logo.png" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700 transition">
                        Save SEO Settings
                    </button>
                </div>
            </div>
        </form>

    {{-- ==================== SERVICES TAB ==================== --}}
    @else
        <div class="flex justify-between items-center mb-4">
            <p class="text-sm text-gray-500">Manage the service cards displayed on the landing page.</p>
            <a href="{{ route('cms.services.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition">
                + Add Service Card
            </a>
        </div>

        @if ($services->isNotEmpty())
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Icon</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($services as $service)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $service->sort_order }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $service->title }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $service->description }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600">
                                        <span class="w-3 h-3 rounded-full {{ \App\Models\SiteService::$iconPalette[$service->icon]['bg'] ?? 'bg-gray-100' }}"></span>
                                        {{ ucfirst($service->icon) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($service->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">Hidden</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <form method="POST" action="{{ route('cms.services.toggle', $service) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-amber-600 hover:text-amber-800 font-medium">{{ $service->is_active ? 'Hide' : 'Show' }}</button>
                                    </form>
                                    <a href="{{ route('cms.services.edit', $service) }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                                    <form method="POST" action="{{ route('cms.services.destroy', $service) }}" class="inline" data-confirm="Delete this service card?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <p class="text-gray-400">No service cards yet. Click "Add Service Card" to create one.</p>
            </div>
        @endif
    @endif
</div>
</x-app-layout>
