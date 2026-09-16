<?php
// Temporary one-off diagnostic — run once, then delete this file.
// Shows the tail of the Laravel log so we can see the real error behind a
// 500 on the payment save.

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';

$logFile = __DIR__.'/../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "No log file found at storage/logs/laravel.log\n";
} else {
    $file = new SplFileObject($logFile, 'r');
    $file->seek(PHP_INT_MAX);
    $lastLine = $file->key();

    $linesToShow = 300;
    $startLine = max(0, $lastLine - $linesToShow);

    $file->seek($startLine);
    while (!$file->eof()) {
        echo htmlspecialchars($file->fgets());
    }
}

echo '</pre>';
