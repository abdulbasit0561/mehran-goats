<?php

namespace Database\Seeders;

use App\Models\SiteService;
use Illuminate\Database\Seeder;

class SiteServiceSeeder extends Seeder
{
    private array $services = [
        [
            'title'       => 'Premium Goat Breeding',
            'description' => 'We breed high-quality, genetically superior goats using proven bloodlines. Our breeding program focuses on health, size, and milk/meat yield.',
            'icon'        => 'emerald',
            'sort_order'  => 1,
        ],
        [
            'title'       => 'Livestock Sales',
            'description' => 'Purchase healthy, well-documented goats directly from our farm. Every animal comes with full health and lineage records for complete transparency.',
            'icon'        => 'blue',
            'sort_order'  => 2,
        ],
        [
            'title'       => 'Livestock Investment',
            'description' => 'Invest in our farm with transparent, real-time tracking of your capital. Earn returns from livestock growth and sales with full profit visibility.',
            'icon'        => 'teal',
            'sort_order'  => 3,
        ],
        [
            'title'       => 'Farm Consulting',
            'description' => 'Expert advice for new and existing goat farmers. From breed selection to housing, nutrition, and health programs — we help you succeed.',
            'icon'        => 'amber',
            'sort_order'  => 4,
        ],
        [
            'title'       => 'Health & Veterinary Care',
            'description' => 'Comprehensive vaccination, deworming, and veterinary programs. Every goat in our care receives regular health monitoring and treatment.',
            'icon'        => 'rose',
            'sort_order'  => 5,
        ],
        [
            'title'       => 'Management Platform',
            'description' => 'Our proprietary platform tracks livestock, investors, finances, and breeding records — giving our team and investors complete transparency.',
            'icon'        => 'indigo',
            'sort_order'  => 6,
        ],
    ];

    public function run(): void
    {
        foreach ($this->services as $service) {
            SiteService::updateOrCreate(
                ['title' => $service['title']],
                $service,
            );
        }
    }
}
