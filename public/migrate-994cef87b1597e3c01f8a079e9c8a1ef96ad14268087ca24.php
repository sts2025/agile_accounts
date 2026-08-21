<?php

/**
 * TEMPORARY one-time migration runner for hosts without SSH/CLI access.
 * Lives directly in public/ and bootstraps Laravel manually so it runs
 * even if the app's route cache is stale (it never goes through the
 * router at all). Gated only by this unguessable filename.
 *
 * DELETE THIS FILE once migrations have been run on the live server —
 * do not leave it deployed long-term.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

echo '<pre>' . htmlspecialchars(Illuminate\Support\Facades\Artisan::output()) . '</pre>';
