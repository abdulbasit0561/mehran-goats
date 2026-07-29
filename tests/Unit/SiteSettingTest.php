<?php

namespace Tests\Unit;

use App\Models\SiteService;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_and_get_single_setting(): void
    {
        SiteSetting::set('test_key', 'test_value');

        $this->assertSame('test_value', SiteSetting::get('test_key'));
    }

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertNull(SiteSetting::get('nonexistent_key'));
        $this->assertSame('fallback', SiteSetting::get('nonexistent_key', 'fallback'));
    }

    public function test_set_updates_existing_key(): void
    {
        SiteSetting::set('test_key', 'original');
        SiteSetting::set('test_key', 'updated');

        $this->assertSame('updated', SiteSetting::get('test_key'));
        $this->assertDatabaseHas('site_settings', [
            'key'   => 'test_key',
            'value' => 'updated',
        ]);
    }

    public function test_all_as_array_returns_associative_array(): void
    {
        SiteSetting::set('key_a', 'value_a');
        SiteSetting::set('key_b', 'value_b');

        $array = SiteSetting::allAsArray();

        $this->assertSame('value_a', $array['key_a']);
        $this->assertSame('value_b', $array['key_b']);
    }

    public function test_cache_is_flushed_on_write(): void
    {
        // First read populates cache
        SiteSetting::set('flush_test', 'first');
        SiteSetting::get('flush_test');
        // Write should flush cache
        SiteSetting::set('flush_test', 'second');
        $this->assertSame('second', SiteSetting::get('flush_test'));
    }
}
