<?php
// TEMPORARY one-time migration runner. DELETE once migrations have run.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$out = '';
foreach (['config:clear', 'cache:clear', 'route:clear', 'view:clear'] as $cmd) {
    Illuminate\Support\Facades\Artisan::call($cmd);
    $out .= "\$ php artisan {$cmd}\n" . Illuminate\Support\Facades\Artisan::output() . "\n";
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$out .= "\$ php artisan migrate --force\n" . Illuminate\Support\Facades\Artisan::output();

echo '<pre>' . htmlspecialchars($out) . '</pre>';
