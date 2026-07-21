<?php

namespace Tests\Feature;

use App\Models\Breed;
use App\Models\Goat;
use App\Models\GoatImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoatImageGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
        $this->actingAs($this->user);

        $this->goat = Goat::create([
            'tag_id'    => 'TAG-GAL',
            'breed_id'  => $this->breed->id,
            'gender'    => 'male',
            'status'    => 'kid',
            'weight_kg' => 15.5,
            'dob'       => '2025-01-01',
        ]);
    }

    //---------------------------------------------------------------------
    // Gallery image store
    //---------------------------------------------------------------------

    public function test_gallery_image_can_be_uploaded_with_required_metadata(): void
    {
        $file = UploadedFile::fake()->image('goat.jpg', 800, 600);

        $response = $this->post("/goats/{$this->goat->id}/images", [
            'image'      => $file,
            'age_months' => 6.5,
            'weight_kg'  => 18.2,
            'taken_at'   => '2026-07-15',
            'notes'      => 'Healthy growth',
        ]);

        $response->assertRedirect("/goats/{$this->goat->id}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('goat_images', [
            'goat_id'    => $this->goat->id,
            'age_months' => 6.5,
            'weight_kg'  => 18.2,
            'taken_at'   => '2026-07-15',
            'notes'      => 'Healthy growth',
        ]);

        // Image file should be stored
        $image = GoatImage::first();
        Storage::disk('public')->assertExists($image->image_path);
    }

    public function test_gallery_image_requires_image_file(): void
    {
        $response = $this->post("/goats/{$this->goat->id}/images", [
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => '2026-07-15',
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_gallery_image_requires_age_months(): void
    {
        $file = UploadedFile::fake()->image('goat.jpg');

        $response = $this->post("/goats/{$this->goat->id}/images", [
            'image'     => $file,
            'weight_kg' => 18,
            'taken_at'  => '2026-07-15',
        ]);

        $response->assertSessionHasErrors('age_months');
    }

    public function test_gallery_image_requires_weight_kg(): void
    {
        $file = UploadedFile::fake()->image('goat.jpg');

        $response = $this->post("/goats/{$this->goat->id}/images", [
            'image'      => $file,
            'age_months' => 6,
            'taken_at'   => '2026-07-15',
        ]);

        $response->assertSessionHasErrors('weight_kg');
    }

    public function test_gallery_image_requires_taken_at(): void
    {
        $file = UploadedFile::fake()->image('goat.jpg');

        $response = $this->post("/goats/{$this->goat->id}/images", [
            'image'      => $file,
            'age_months' => 6,
            'weight_kg'  => 18,
        ]);

        $response->assertSessionHasErrors('taken_at');
    }

    public function test_gallery_image_taken_at_cannot_be_future_date(): void
    {
        $file = UploadedFile::fake()->image('goat.jpg');

        $response = $this->post("/goats/{$this->goat->id}/images", [
            'image'      => $file,
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('taken_at');
    }

    //---------------------------------------------------------------------
    // Gallery image delete
    //---------------------------------------------------------------------

    public function test_gallery_image_can_be_deleted(): void
    {
        $file = UploadedFile::fake()->image('goat.jpg');
        $path = $file->store('goats/gallery', 'public');

        $image = GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => $path,
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => '2026-07-15',
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->delete("/goats/images/{$image->id}");

        $response->assertRedirect("/goats/{$this->goat->id}");
        $this->assertSoftDeleted('goat_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    //---------------------------------------------------------------------
    // Profile photo → gallery sync
    //---------------------------------------------------------------------

    public function test_setting_profile_photo_creates_gallery_entry(): void
    {
        $file = UploadedFile::fake()->image('profile.jpg');

        $response = $this->post('/goats', [
            'tag_id'    => 'TAG-PHOTO',
            'breed_id'  => $this->breed->id,
            'gender'    => 'male',
            'status'    => 'kid',
            'weight_kg' => 10,
            'photo'     => $file,
        ]);

        $goat = Goat::where('tag_id', 'TAG-PHOTO')->first();
        $this->assertNotNull($goat->photo);
        $this->assertDatabaseHas('goat_images', [
            'goat_id'    => $goat->id,
            'image_path' => $goat->photo,
            'notes'      => 'Profile photo',
        ]);
    }

    public function test_updating_profile_photo_creates_new_gallery_entry(): void
    {
        $file = UploadedFile::fake()->image('new-profile.jpg');

        $response = $this->put("/goats/{$this->goat->id}", [
            'tag_id'    => $this->goat->tag_id,
            'breed_id'  => $this->breed->id,
            'gender'    => 'male',
            'status'    => 'kid',
            'photo'     => $file,
        ]);

        $this->goat->refresh();
        $this->assertNotNull($this->goat->photo);
        $this->assertDatabaseHas('goat_images', [
            'goat_id'    => $this->goat->id,
            'image_path' => $this->goat->photo,
        ]);
    }

    public function test_updating_goat_without_photo_does_not_create_gallery_entry(): void
    {
        $response = $this->put("/goats/{$this->goat->id}", [
            'tag_id'   => $this->goat->tag_id,
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'breeder',
        ]);

        $this->assertEquals(0, GoatImage::where('goat_id', $this->goat->id)->count());
    }

    //---------------------------------------------------------------------
    // Gallery display on show page
    //---------------------------------------------------------------------

    public function test_show_page_displays_gallery_images(): void
    {
        $file = UploadedFile::fake()->image('goat.jpg');
        $path = $file->store('goats/gallery', 'public');

        GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => $path,
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => '2026-07-15',
            'notes'      => 'Summer photo',
        ]);

        $response = $this->get("/goats/{$this->goat->id}");

        $response->assertStatus(200);
        $response->assertSee('Photo Gallery');
        $response->assertSee('Summer photo');
    }

    public function test_show_page_shows_empty_state_when_no_gallery_images(): void
    {
        $response = $this->get("/goats/{$this->goat->id}");

        $response->assertStatus(200);
        $response->assertSee('No photos yet');
    }

    //---------------------------------------------------------------------
    // Model tests
    //---------------------------------------------------------------------

    public function test_goat_image_belongs_to_goat(): void
    {
        $image = GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => 'goats/gallery/test.jpg',
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => '2026-07-15',
        ]);

        $this->assertEquals($this->goat->id, $image->goat->id);
    }

    public function test_goat_has_many_images(): void
    {
        GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => 'goats/gallery/test1.jpg',
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => '2026-07-15',
        ]);
        GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => 'goats/gallery/test2.jpg',
            'age_months' => 7,
            'weight_kg'  => 20,
            'taken_at'   => '2026-08-15',
        ]);

        $this->assertCount(2, $this->goat->fresh()->images);
    }

    public function test_goat_images_ordered_newest_first(): void
    {
        $older = GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => 'goats/gallery/old.jpg',
            'age_months' => 3,
            'weight_kg'  => 10,
            'taken_at'   => '2026-01-15',
        ]);
        $newer = GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => 'goats/gallery/new.jpg',
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => '2026-07-15',
        ]);

        $images = $this->goat->fresh()->images;
        $this->assertEquals('2026-07-15', $images->first()->taken_at->format('Y-m-d'));
        $this->assertEquals('2026-01-15', $images->last()->taken_at->format('Y-m-d'));
    }

    public function test_formatted_date_accessor(): void
    {
        $image = GoatImage::create([
            'goat_id'    => $this->goat->id,
            'image_path' => 'goats/gallery/test.jpg',
            'age_months' => 6,
            'weight_kg'  => 18,
            'taken_at'   => '2026-07-15',
        ]);

        $this->assertEquals('Jul 15, 2026', $image->formatted_date);
    }
}
