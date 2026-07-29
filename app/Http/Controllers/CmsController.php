<?php

namespace App\Http\Controllers;

use App\Models\SiteService;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CmsController extends Controller
{
    /**
     * CMS dashboard — tabbed interface for branding, general settings, SEO, and services.
     */
    public function index()
    {
        $settings = SiteSetting::allAsArray();
        $services = SiteService::ordered()->get();

        return view('cms.index', compact('settings', 'services'));
    }

    /**
     * Update branding settings — logo, favicon, page title, theme colour.
     * Handles file uploads for logo and favicon.
     */
    public function updateBranding(Request $request)
    {
        $validated = $request->validate([
            'site_page_title'  => ['nullable', 'string', 'max:200'],
            'site_theme_color' => ['nullable', 'string', 'max:20'],
            'site_logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:1024'],
            'site_favicon'     => ['nullable', 'image', 'mimes:png,ico,svg', 'max:512'],
            'site_apple_icon'  => ['nullable', 'image', 'mimes:png', 'max:512'],
            'remove_logo'      => ['nullable', 'boolean'],
            'remove_favicon'   => ['nullable', 'boolean'],
            'remove_apple_icon' => ['nullable', 'boolean'],
        ]);

        // Text settings
        if (isset($validated['site_page_title'])) {
            SiteSetting::set('site_page_title', $validated['site_page_title']);
        }
        if (isset($validated['site_theme_color'])) {
            SiteSetting::set('site_theme_color', $validated['site_theme_color']);
        }

        // Logo upload
        if (!empty($validated['remove_logo'])) {
            $this->removeUpload('site_logo');
            SiteSetting::set('site_logo', '');
        } elseif ($request->hasFile('site_logo')) {
            $this->removeUpload('site_logo');
            $path = $request->file('site_logo')->store('cms', 'public');
            SiteSetting::set('site_logo', $path);
        }

        // Favicon upload
        if (!empty($validated['remove_favicon'])) {
            $this->removeUpload('site_favicon');
            SiteSetting::set('site_favicon', '');
        } elseif ($request->hasFile('site_favicon')) {
            $this->removeUpload('site_favicon');
            $path = $request->file('site_favicon')->store('cms', 'public');
            SiteSetting::set('site_favicon', $path);
        }

        // Apple touch icon upload
        if (!empty($validated['remove_apple_icon'])) {
            $this->removeUpload('site_apple_icon');
            SiteSetting::set('site_apple_icon', '');
        } elseif ($request->hasFile('site_apple_icon')) {
            $this->removeUpload('site_apple_icon');
            $path = $request->file('site_apple_icon')->store('cms', 'public');
            SiteSetting::set('site_apple_icon', $path);
        }

        return redirect()->route('cms.index', ['tab' => 'branding'])
            ->with('success', 'Branding settings updated successfully.');
    }

    /**
     * Delete a previously uploaded file from storage.
     */
    private function removeUpload(string $key): void
    {
        $path = SiteSetting::get($key);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Update general site content settings (hero, about, contact, etc.).
     */
    public function updateGeneral(Request $request)
    {
        $generalKeys = [
            'site_name', 'site_tagline',
            'farm_video_title', 'farm_video_subtitle',
            'hero_badge', 'hero_title', 'hero_subtitle',
            'hero_cta_primary_text', 'hero_cta_secondary_text',
            'hero_stats_title', 'hero_trust_text',
            'stat_goats', 'stat_goats_label',
            'stat_breeds', 'stat_breeds_label',
            'stat_investors', 'stat_investors_label',
            'stat_years', 'stat_years_label',
            'stats_band_goats', 'stats_band_goats_label',
            'stats_band_investors', 'stats_band_investors_label',
            'stats_band_breeds', 'stats_band_breeds_label',
            'stats_band_years', 'stats_band_years_label',
            'about_badge', 'about_title', 'about_text_1', 'about_text_2',
            'about_highlight_1_title', 'about_highlight_1_desc',
            'about_highlight_2_title', 'about_highlight_2_desc',
            'about_highlight_3_title', 'about_highlight_3_desc',
            'services_section_badge', 'services_section_title', 'services_section_subtitle',
            'why_us_section_badge', 'why_us_section_title', 'why_us_section_subtitle',
            'why_us_1_title', 'why_us_1_desc',
            'why_us_2_title', 'why_us_2_desc',
            'why_us_3_title', 'why_us_3_desc',
            'why_us_4_title', 'why_us_4_desc',
            'contact_badge', 'contact_title', 'contact_subtitle',
            'contact_email', 'contact_phone', 'contact_location',
            'contact_email_label', 'contact_phone_label', 'contact_location_label',
            'footer_about',
        ];

        $validated = $request->validate(
            collect($generalKeys)->mapWithKeys(fn ($k) => [$k => ['nullable', 'string']])->toArray(),
        );

        foreach ($validated as $key => $value) {
            SiteSetting::set($key, $value ?? '');
        }

        return redirect()->route('cms.index', ['tab' => 'general'])
            ->with('success', 'General content updated successfully.');
    }

    /**
     * Update SEO settings.
     */
    public function updateSeo(Request $request)
    {
        $seoKeys = [
            'seo_title', 'seo_description', 'seo_keywords',
            'seo_og_image', 'seo_og_type', 'seo_twitter_card',
            'seo_canonical_url',
            'seo_robots_index', 'seo_robots_follow',
            'seo_structured_data_type', 'seo_structured_data_name',
            'seo_structured_data_url', 'seo_structured_data_logo',
        ];

        $validated = $request->validate(
            collect($seoKeys)->mapWithKeys(fn ($k) => [$k => ['nullable', 'string']])->toArray(),
        );

        foreach ($validated as $key => $value) {
            SiteSetting::set($key, $value ?? '');
        }

        return redirect()->route('cms.index', ['tab' => 'seo'])
            ->with('success', 'SEO settings updated successfully.');
    }

    // ─── Service cards CRUD ─────────────────────────────────────────────

    public function servicesCreate()
    {
        return view('cms.services-form', ['service' => null]);
    }

    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon'        => ['required', Rule::in(array_keys(SiteService::$iconPalette))],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        SiteService::create([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? '',
            'icon'        => $validated['icon'],
            'sort_order'  => $validated['sort_order'] ?? 0,
            'is_active'   => true,
        ]);

        return redirect()->route('cms.index', ['tab' => 'services'])
            ->with('success', 'Service card created.');
    }

    public function servicesEdit(SiteService $service)
    {
        return view('cms.services-form', compact('service'));
    }

    public function servicesUpdate(Request $request, SiteService $service)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon'        => ['required', Rule::in(array_keys(SiteService::$iconPalette))],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $service->update([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? '',
            'icon'        => $validated['icon'],
            'sort_order'  => $validated['sort_order'] ?? 0,
            'is_active'   => $validated['is_active'] ?? false,
        ]);

        return redirect()->route('cms.index', ['tab' => 'services'])
            ->with('success', 'Service card updated.');
    }

    public function servicesDestroy(SiteService $service)
    {
        $service->delete();

        return redirect()->route('cms.index', ['tab' => 'services'])
            ->with('success', 'Service card deleted.');
    }

    public function servicesToggle(SiteService $service)
    {
        $service->update(['is_active' => ! $service->is_active]);

        return redirect()->route('cms.index', ['tab' => 'services'])
            ->with('success', 'Service card visibility toggled.');
    }
}
