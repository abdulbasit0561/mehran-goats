<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root URL redirects to the dashboard (which requires auth → redirects to login).
     */
    public function test_the_application_root_redirects(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
