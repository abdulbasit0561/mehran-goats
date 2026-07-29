<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * All default settings matching the current hardcoded landing page.
     */
    private array $settings = [
        // --- Site identity ---
        'site_name'                  => 'Mehran Goats',
        'site_tagline'               => 'Livestock Company',
        'site_page_title'            => 'Mehran Goats — Livestock Management',
        'site_theme_color'           => '#059669',
        'site_logo'                  => '',
        'site_favicon'               => '',
        'site_apple_icon'            => '',

        // --- Farm video banner ---
        'farm_video_title'           => 'Welcome to Our Farm',
        'farm_video_subtitle'        => 'See our goats grazing freely on lush green pastures — healthy, happy, and well-cared for.',

        // --- Hero ---
        'hero_badge'                 => 'Trusted Goat Farming Company Since 2018',
        'hero_title'                 => 'Premium Goat Farming & Livestock Company',
        'hero_subtitle'              => 'Mehran Goats is a leading livestock company specializing in premium goat breeding, sustainable farming practices, and profitable livestock investment opportunities.',
        'hero_cta_primary_text'      => 'Explore Our Services',
        'hero_cta_secondary_text'    => 'Get in Touch',
        'hero_stats_title'           => 'Company at a Glance',
        'hero_trust_text'            => 'Trusted by farmers & investors nationwide',

        // --- Stats ---
        'stat_goats'                 => '500+',
        'stat_goats_label'           => 'Goats in Our Herd',
        'stat_breeds'                => '12+',
        'stat_breeds_label'          => 'Premium Breeds',
        'stat_investors'             => '50+',
        'stat_investors_label'       => 'Satisfied Investors',
        'stat_years'                 => '7+',
        'stat_years_label'           => 'Years of Excellence',

        // --- Stats band ---
        'stats_band_goats'           => '500+',
        'stats_band_goats_label'     => 'Goats Raised',
        'stats_band_investors'       => '50+',
        'stats_band_investors_label' => 'Active Investors',
        'stats_band_breeds'          => '12+',
        'stats_band_breeds_label'    => 'Premium Breeds',
        'stats_band_years'           => '7+',
        'stats_band_years_label'     => 'Years in Business',

        // --- About ---
        'about_badge'                => 'About Our Company',
        'about_title'                => 'Building the Future of Sustainable Goat Farming',
        'about_text_1'               => 'Founded in 2018, Mehran Goats started as a small family farm with a big vision: to transform goat farming into a modern, data-driven, and highly profitable enterprise. Today we are one of the region\'s most respected livestock companies.',
        'about_text_2'               => 'We combine traditional farming wisdom with modern technology to raise healthy, premium-quality goats. Our in-house management platform — used by our team and trusted investors — tracks every animal, every investment, and every naira in real time.',
        'about_highlight_1_title'    => 'Modern Farming Methods',
        'about_highlight_1_desc'     => 'Technology-driven breeding, health monitoring, and nutritional programs.',
        'about_highlight_2_title'    => 'Transparent Investment',
        'about_highlight_2_desc'     => 'Investors get real-time dashboards showing exactly how their capital is performing.',
        'about_highlight_3_title'    => 'Sustainable Practices',
        'about_highlight_3_desc'     => 'Eco-conscious farming that respects animal welfare and the environment.',

        // --- Services section ---
        'services_section_badge'     => 'What We Offer',
        'services_section_title'     => 'Our Services & Business Divisions',
        'services_section_subtitle'  => 'From premium livestock breeding to investment management — everything you need under one trusted company.',

        // --- Why Us ---
        'why_us_section_badge'       => 'Our Commitment',
        'why_us_section_title'       => 'Why Choose Mehran Goats?',
        'why_us_section_subtitle'    => 'We\'re not just a farm — we\'re a company built on trust, transparency, and results.',
        'why_us_1_title'             => 'Proven Track Record',
        'why_us_1_desc'              => '7+ years of successful breeding, sales, and investor returns. Our numbers speak for themselves.',
        'why_us_2_title'             => 'Full Transparency',
        'why_us_2_desc'              => 'Investors see every animal, every expense, and every return in real time. No hidden fees, no guesswork.',
        'why_us_3_title'             => 'Premium Quality',
        'why_us_3_desc'              => 'Every goat is bred for health, size, and productivity. We never compromise on animal welfare.',
        'why_us_4_title'             => 'Dedicated Support',
        'why_us_4_desc'              => 'Our team is always available for investors, buyers, and consulting clients. Personal service, every time.',

        // --- Contact ---
        'contact_badge'              => 'Get in Touch',
        'contact_title'              => 'Ready to Work with Mehran Goats?',
        'contact_subtitle'           => 'Whether you\'re looking to buy premium livestock, invest in our farm, or need expert consulting — we\'d love to hear from you.',
        'contact_email'              => 'info@mehrangoats.com',
        'contact_phone'              => '+92 300 0000000',
        'contact_location'           => 'Contact for location',
        'contact_email_label'        => 'Email Us',
        'contact_phone_label'        => 'Call Us',
        'contact_location_label'     => 'Visit Our Farm',

        // --- Footer ---
        'footer_about'               => 'A premier goat farming and livestock company. Premium breeding, sustainable farming, and transparent investment.',

        // === SEO Settings ===
        'seo_title'                  => 'Mehran Goats — Premium Goat Farming & Livestock Company',
        'seo_description'            => 'Mehran Goats is a premier goat farming company specializing in livestock breeding, investment management, and sustainable farming solutions.',
        'seo_keywords'               => 'goat farming, livestock breeding, goat investment, sustainable farming, premium goats, Mehran Goats',
        'seo_og_image'               => '',
        'seo_og_type'                => 'website',
        'seo_twitter_card'           => 'summary_large_image',
        'seo_canonical_url'          => '',
        'seo_robots_index'           => 'index',
        'seo_robots_follow'          => 'follow',
        'seo_structured_data_type'   => 'Organization',
        'seo_structured_data_name'   => 'Mehran Goats',
        'seo_structured_data_url'    => '',
        'seo_structured_data_logo'   => '',
    ];

    public function run(): void
    {
        foreach ($this->settings as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
