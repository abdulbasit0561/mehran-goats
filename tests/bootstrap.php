<?php

/**
 * PHPUnit bootstrap.
 *
 * Laravel's phpunit.xml env overrides (e.g. DB_DATABASE pointing at the
 * *_testing database) are silently ignored when a cached config exists
 * (bootstrap/cache/config.php) — see .drytis/notes/test-db-isolation-fix.md,
 * where exactly this wiped the live dev database.
 *
 * This bootstrap deletes any cached config/routes/views BEFORE the framework
 * boots in tests, guaranteeing phpunit.xml values always win.
 */

$caches = [
    __DIR__ . '/../bootstrap/cache/config.php',
    __DIR__ . '/../bootstrap/cache/routes-v7.php',
    __DIR__ . '/../bootstrap/cache/services.php',
    __DIR__ . '/../bootstrap/cache/packages.php',
];

foreach ($caches as $file) {
    if (is_file($file)) {
        @unlink($file);
    }
}

require __DIR__ . '/../vendor/autoload.php';
