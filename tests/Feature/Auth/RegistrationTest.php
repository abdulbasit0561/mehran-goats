<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_cannot_be_accessed(): void
    {
        // Registration is disabled for this private farm management app.
        // Admin accounts are seeded via DatabaseSeeder.
        $response = $this->get('/register');

        $response->assertNotFound();
    }
}
