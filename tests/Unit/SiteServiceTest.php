<?php

namespace Tests\Unit;

use App\Models\SiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_filters_inactive(): void
    {
        $active   = SiteService::factory()->create(['is_active' => true]);
        $inactive = SiteService::factory()->create(['is_active' => false]);

        $result = SiteService::active()->get();

        $this->assertTrue($result->contains($active));
        $this->assertFalse($result->contains($inactive));
    }

    public function test_ordered_scope_sorts_by_sort_order_then_id(): void
    {
        $first  = SiteService::factory()->create(['sort_order' => 1]);
        $third  = SiteService::factory()->create(['sort_order' => 10]);
        $second = SiteService::factory()->create(['sort_order' => 5]);

        $result = SiteService::ordered()->get();

        $this->assertSame(3, $result->count());
        $this->assertSame($first->id, $result->first()->id);
        $this->assertSame($third->id, $result->last()->id);
    }

    public function test_icon_classes_returns_palette_for_known_icon(): void
    {
        $service = SiteService::factory()->create(['icon' => 'blue']);

        $this->assertSame('bg-blue-100', $service->icon_classes['bg']);
        $this->assertSame('text-blue-600', $service->icon_classes['text']);
    }

    public function test_icon_classes_falls_back_to_emerald_for_unknown_icon(): void
    {
        $service = SiteService::factory()->create(['icon' => 'unknown']);

        $this->assertSame('bg-emerald-100', $service->icon_classes['bg']);
        $this->assertSame('text-emerald-600', $service->icon_classes['text']);
    }

    public function test_icon_svg_returns_path_for_known_icon(): void
    {
        $service = SiteService::factory()->create(['icon' => 'amber']);

        $this->assertStringContainsString('stroke-linecap', $service->icon_svg);
    }

    public function test_icon_svg_falls_back_to_emerald_for_unknown_icon(): void
    {
        $service = SiteService::factory()->create(['icon' => 'unknown']);

        $this->assertStringContainsString('stroke-linecap', $service->icon_svg);
    }

    public function test_factory_creates_valid_service(): void
    {
        $service = SiteService::factory()->create();

        $this->assertNotNull($service->title);
        $this->assertNotNull($service->icon);
        $this->assertNotNull($service->sort_order);
        $this->assertNotNull($service->is_active);
    }
}
