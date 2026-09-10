<?php
// Temporary one-off deploy helper — run once, then delete this file.
// Boots Laravel manually (bypassing the normal HTTP route stack) and runs
// the pending-migrations command outside of route caching, so a stale
// route cache can't mask a freshly deployed migration.

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';

$commands = ['config:clear', 'cache:clear', 'route:clear', 'view:clear'];
foreach ($commands as $command) {
    \Illuminate\Support\Facades\Artisan::call($command);
    echo "$command ................. DONE\n";
    echo \Illuminate\Support\Facades\Artisan::output();
}

\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo "migrate ................. DONE\n";
echo \Illuminate\Support\Facades\Artisan::output();

echo '</pre>';
