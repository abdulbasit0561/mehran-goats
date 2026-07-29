<?php

namespace Tests\Feature;

use App\Models\SiteService;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── CMS Index ──────────────────────────────────────────────────────

    public function test_cms_index_requires_auth(): void
    {
        $response = $this->get(route('cms.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_cms_index_displays_tabs(): void
    {
        $response = $this->actingAs($this->user)->get(route('cms.index'));
        $response->assertOk();
        $response->assertSee('Branding');
        $response->assertSee('General Content');
        $response->assertSee('SEO Settings');
        $response->assertSee('Service Cards');
    }

    // ─── Branding settings update ──────────────────────────────────────

    public function test_update_branding_saves_page_title_and_theme(): void
    {
        $response = $this->actingAs($this->user)->post(route('cms.update-branding'), [
            'site_page_title'  => 'My Custom Page Title',
            'site_theme_color' => '#ff0000',
        ]);

        $response->assertRedirect(route('cms.index', ['tab' => 'branding']));
        $response->assertSessionHas('success');

        $this->assertSame('My Custom Page Title', SiteSetting::get('site_page_title'));
        $this->assertSame('#ff0000', SiteSetting::get('site_theme_color'));
    }

    public function test_update_branding_requires_auth(): void
    {
        $response = $this->post(route('cms.update-branding'), [
            'site_page_title' => 'Hacked',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_update_branding_uploads_logo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $response = $this->actingAs($this->user)->post(route('cms.update-branding'), [
            'site_page_title' => 'Test',
            'site_logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $response->assertRedirect(route('cms.index', ['tab' => 'branding']));

        $logoPath = SiteSetting::get('site_logo');
        $this->assertNotEmpty($logoPath);
        $this->assertStringStartsWith('cms/', $logoPath);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($logoPath);
    }

    public function test_update_branding_removes_logo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        // First upload a logo
        $this->actingAs($this->user)->post(route('cms.update-branding'), [
            'site_logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200),
        ]);
        $logoPath = SiteSetting::get('site_logo');
        $this->assertNotEmpty($logoPath);

        // Now remove it
        $response = $this->actingAs($this->user)->post(route('cms.update-branding'), [
            'remove_logo' => 1,
        ]);

        $response->assertRedirect(route('cms.index', ['tab' => 'branding']));
        $this->assertSame('', SiteSetting::get('site_logo'));
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_update_branding_validates_logo_file_type(): void
    {
        $response = $this->actingAs($this->user)->post(route('cms.update-branding'), [
            'site_logo' => \Illuminate\Http\UploadedFile::fake()->create('malicious.exe', 100, 'application/octet-stream'),
        ]);

        $response->assertSessionHasErrors('site_logo');
    }

    // ─── General settings update ───────────────────────────────────────

    public function test_update_general_saves_settings(): void
    {
        $response = $this->actingAs($this->user)->post(route('cms.update-general'), [
            'site_name'  => 'Updated Farm Name',
            'hero_title' => 'New Hero Title',
            'stat_goats' => '999+',
        ]);

        $response->assertRedirect(route('cms.index', ['tab' => 'general']));
        $response->assertSessionHas('success');

        $this->assertSame('Updated Farm Name', SiteSetting::get('site_name'));
        $this->assertSame('New Hero Title', SiteSetting::get('hero_title'));
        $this->assertSame('999+', SiteSetting::get('stat_goats'));
    }

    public function test_update_general_requires_auth(): void
    {
        $response = $this->post(route('cms.update-general'), [
            'site_name' => 'Hacked',
        ]);
        $response->assertRedirect(route('login'));
    }

    // ─── SEO settings update ───────────────────────────────────────────

    public function test_update_seo_saves_settings(): void
    {
        $response = $this->actingAs($this->user)->post(route('cms.update-seo'), [
            'seo_title'         => 'SEO Title Test',
            'seo_description'   => 'SEO Description Test',
            'seo_keywords'      => 'goats, farm, livestock',
            'seo_robots_index'  => 'noindex',
            'seo_robots_follow' => 'nofollow',
        ]);

        $response->assertRedirect(route('cms.index', ['tab' => 'seo']));
        $response->assertSessionHas('success');

        $this->assertSame('SEO Title Test', SiteSetting::get('seo_title'));
        $this->assertSame('noindex', SiteSetting::get('seo_robots_index'));
        $this->assertSame('nofollow', SiteSetting::get('seo_robots_follow'));
    }

    // ─── Service cards CRUD ───────────────────────────────────────────

    public function test_create_service_card(): void
    {
        $response = $this->actingAs($this->user)->post(route('cms.services.store'), [
            'title'       => 'New Service',
            'description' => 'A test service card.',
            'icon'        => 'blue',
            'sort_order'  => 5,
        ]);

        $response->assertRedirect(route('cms.index', ['tab' => 'services']));
        $this->assertDatabaseHas('site_services', [
            'title'      => 'New Service',
            'icon'       => 'blue',
            'sort_order' => 5,
            'is_active'  => true,
        ]);
    }

    public function test_edit_service_card(): void
    {
        $service = SiteService::factory()->create([
            'title'       => 'Original Title',
            'is_active'   => true,
        ]);

        $response = $this->actingAs($this->user)->put(route('cms.services.update', $service), [
            'title'       => 'Updated Title',
            'description' => 'Updated description.',
            'icon'        => 'amber',
            'sort_order'  => 10,
            'is_active'   => false,
        ]);

        $response->assertRedirect(route('cms.index', ['tab' => 'services']));
        $service->refresh();
        $this->assertSame('Updated Title', $service->title);
        $this->assertFalse($service->is_active);
    }

    public function test_delete_service_card(): void
    {
        $service = SiteService::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('cms.services.destroy', $service));

        $response->assertRedirect(route('cms.index', ['tab' => 'services']));
        $this->assertDatabaseMissing('site_services', ['id' => $service->id]);
    }

    public function test_toggle_service_card_visibility(): void
    {
        $service = SiteService::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->post(route('cms.services.toggle', $service));

        $response->assertRedirect(route('cms.index', ['tab' => 'services']));
        $service->refresh();
        $this->assertFalse($service->is_active);

        // Toggle back
        $this->actingAs($this->user)->post(route('cms.services.toggle', $service));
        $service->refresh();
        $this->assertTrue($service->is_active);
    }

    public function test_service_store_validates_icon(): void
    {
        $response = $this->actingAs($this->user)->post(route('cms.services.store'), [
            'title' => 'Bad Icon',
            'icon'  => 'nonexistent_colour',
        ]);

        $response->assertSessionHasErrors('icon');
    }

    // ─── Landing page renders CMS content ───────────────────────────────

    public function test_landing_page_renders_seo_meta_tags(): void
    {
        SiteSetting::set('seo_title', 'Test SEO Title');
        SiteSetting::set('seo_description', 'Test SEO Description');
        SiteSetting::set('seo_keywords', 'goats, farm, test');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('<title>Test SEO Title</title>', false);
        $response->assertSee('name="description" content="Test SEO Description"', false);
        $response->assertSee('name="keywords" content="goats, farm, test"', false);
        $response->assertSee('name="robots"', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('application/ld+json', false);
    }

    public function test_landing_page_renders_cms_general_content(): void
    {
        SiteSetting::set('site_name', 'Custom Farm Name');
        SiteSetting::set('hero_title', 'Custom Hero Heading');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Custom Farm Name');
        $response->assertSee('Custom Hero Heading');
    }

    public function test_landing_page_renders_active_services_only(): void
    {
        $active   = SiteService::factory()->create(['title' => 'Active Service', 'is_active' => true, 'sort_order' => 1]);
        $inactive = SiteService::factory()->create(['title' => 'Inactive Service', 'is_active' => false, 'sort_order' => 2]);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('Active Service');
        $response->assertDontSee('Inactive Service');
    }

    public function test_landing_page_includes_structured_data_json(): void
    {
        SiteSetting::set('seo_structured_data_name', 'Test Org');
        SiteSetting::set('seo_structured_data_type', 'Organization');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('"@type": "Organization"', false);
        $response->assertSee('"name": "Test Org"', false);
    }

    public function test_landing_page_uses_page_title_when_set(): void
    {
        SiteSetting::set('site_page_title', 'Custom Browser Tab Title');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('<title>Custom Browser Tab Title</title>', false);
    }

    public function test_landing_page_falls_back_to_seo_title_for_page_title(): void
    {
        SiteSetting::set('site_page_title', '');
        SiteSetting::set('seo_title', 'SEO Fallback Title');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('<title>SEO Fallback Title</title>', false);
    }

    public function test_landing_page_uses_custom_theme_color(): void
    {
        SiteSetting::set('site_theme_color', '#abcdef');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('name="theme-color" content="#abcdef"', false);
    }

    public function test_landing_page_uses_custom_logo_when_set(): void
    {
        SiteSetting::set('site_logo', 'cms/test-logo.png');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('storage/cms/test-logo.png', false);
    }

    public function test_landing_page_uses_custom_favicon_when_set(): void
    {
        SiteSetting::set('site_favicon', 'cms/favicon.ico');

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee('storage/cms/favicon.ico', false);
    }
}
