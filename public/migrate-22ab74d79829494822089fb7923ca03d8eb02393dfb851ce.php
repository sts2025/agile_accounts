<?php
// Temporary one-off deploy helper — run once, then delete this file.
// Clears cached config/routes/views (so the new code actually takes
// effect) and runs the pending migrations, echoing the real output
// (including any "Skipped re-pointing..." notices from the loan_manager_id
// foreign-key fix) so we can see exactly what happened.

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';

function runArtisan($kernel, $command, $params = [])
{
    echo "\$ php artisan {$command}\n";
    $exitCode = $kernel->call($command, $params);
    echo htmlspecialchars($kernel->output());
    echo "\n[exit code: {$exitCode}]\n\n";
    echo str_repeat('-', 70) . "\n\n";
}

runArtisan($kernel, 'config:clear');
runArtisan($kernel, 'cache:clear');
runArtisan($kernel, 'route:clear');
runArtisan($kernel, 'view:clear');
runArtisan($kernel, 'migrate', ['--force' => true]);

echo '</pre>';
