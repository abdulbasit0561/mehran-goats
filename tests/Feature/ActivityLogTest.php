<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Breed;
use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->breed = Breed::create(['name' => 'Boer', 'description' => 'Meat goat']);
    }

    public function test_creating_a_goat_generates_an_activity_log(): void
    {
        $this->post('/goats', [
            'tag_id'   => 'G-LOG-001',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'create',
            'entity_type' => Goat::class,
            'user_id'     => $this->user->id,
            'user_name'   => $this->user->name,
        ]);
    }

    public function test_updating_a_goat_captures_old_and_new_values(): void
    {
        $goat = Goat::create([
            'tag_id'   => 'G-LOG-002',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        // Clear the create log
        ActivityLog::query()->delete();

        $this->put("/goats/{$goat->id}", [
            'tag_id'   => 'G-LOG-002',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'breeder',
        ]);

        $log = ActivityLog::where('action', 'update')->first();

        $this->assertNotNull($log);
        $this->assertEquals('kid', $log->old_values['status']);
        $this->assertEquals('breeder', $log->new_values['status']);
    }

    public function test_deleting_a_goat_generates_a_log_entry(): void
    {
        $goat = Goat::create([
            'tag_id'   => 'G-LOG-003',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        ActivityLog::query()->delete();

        $this->delete("/goats/{$goat->id}");

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'delete',
            'entity_type' => Goat::class,
        ]);
    }

    public function test_non_super_admin_cannot_access_activity_logs(): void
    {
        $response = $this->get('/activity-logs');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_view_activity_logs(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);

        $response = $this->get('/activity-logs');

        $response->assertStatus(200);
        $response->assertSee('Activity Logs');
    }

    public function test_super_admin_can_view_single_log_detail(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($superAdmin);

        // Create a log entry via a goat creation
        $this->post('/goats', [
            'tag_id'   => 'G-LOG-004',
            'breed_id' => $this->breed->id,
            'gender'   => 'female',
            'status'   => 'kid',
        ]);

        $log = ActivityLog::first();
        $this->assertNotNull($log);

        $response = $this->get("/activity-logs/{$log->id}");

        $response->assertStatus(200);
        $response->assertSee('Before (Old Values)');
        $response->assertSee('After (New Values)');
    }

    public function test_log_description_is_human_readable(): void
    {
        $this->post('/goats', [
            'tag_id'   => 'G-LOG-005',
            'breed_id' => $this->breed->id,
            'gender'   => 'male',
            'status'   => 'kid',
        ]);

        $log = ActivityLog::where('entity_type', Goat::class)->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Goat', $log->description);
        $this->assertStringContainsString('G-LOG-005', $log->description);
        $this->assertStringContainsString('created', $log->description);
    }

    public function test_sensitive_fields_are_not_logged(): void
    {
        // Create a user — password should not be in the log
        $newUser = User::create([
            'name'     => 'Test User',
            'email'    => 'test.user@example.com',
            'password' => 'secret123',
        ]);

        // User is not in the tracked models list, so no log should be created
        $this->assertDatabaseMissing('activity_logs', [
            'entity_type' => User::class,
        ]);
    }

    public function test_super_admin_seeder_assigns_flag(): void
    {
        // Run the seeder
        $this->artisan('db:seed', ['--class' => 'SuperAdminSeeder']);

        $user = User::where('email', 'abdulbasit056@gmail.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isSuperAdmin());
    }
}
