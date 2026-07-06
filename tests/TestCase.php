<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF protection for all tests — the testing framework
        // doesn't send real CSRF tokens, and PreventRequestForgery (Laravel 11's
        // CSRF guard) blocks all non-GET requests in tests otherwise.
        $this->withoutMiddleware([PreventRequestForgery::class]);
    }
}
