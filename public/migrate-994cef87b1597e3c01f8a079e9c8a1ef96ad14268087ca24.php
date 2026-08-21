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

$out = '';

// Clear any stale cached config/routes left over from before the .htaccess
// fix (a stale cached APP_URL/session config is a common cause of 419 page
// expired / CSRF errors right after a document-root change like this one).
foreach (['config:clear', 'cache:clear', 'route:clear', 'view:clear'] as $cmd) {
    Illuminate\Support\Facades\Artisan::call($cmd);
    $out .= "\$ php artisan {$cmd}\n" . Illuminate\Support\Facades\Artisan::output() . "\n";
}

Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$out .= "\$ php artisan migrate --force\n" . Illuminate\Support\Facades\Artisan::output();

echo '<pre>' . htmlspecialchars($out) . '</pre>';
