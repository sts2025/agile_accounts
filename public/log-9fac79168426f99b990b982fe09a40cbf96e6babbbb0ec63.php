<?php
// Temporary one-off diagnostic — run once, then delete this file.
// The whole site is down (500 on every page), so this turns on raw PHP
// error display first (in case Laravel's own bootstrap is what's broken —
// then even a normal Laravel-aware diagnostic script would 500 the same
// way) before trying to boot Laravel and show the log tail.

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo '<pre>';
echo "PHP version: " . PHP_VERSION . "\n\n";

echo "=== Attempting to load Composer autoloader ===\n";
try {
    require __DIR__.'/../vendor/autoload.php';
    echo "OK\n\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    echo '</pre>';
    exit;
}

echo "=== Attempting to bootstrap Laravel ===\n";
try {
    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "OK\n\n";
} catch (\Throwable $e) {
    echo "FAILED: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "at " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo $e->getTraceAsString() . "\n";
    echo '</pre>';
    exit;
}

echo "=== Laravel log tail (last 400 lines) ===\n\n";

$logFile = __DIR__.'/../storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "No log file found at storage/logs/laravel.log\n";
} else {
    $file = new SplFileObject($logFile, 'r');
    $file->seek(PHP_INT_MAX);
    $lastLine = $file->key();

    $linesToShow = 400;
    $startLine = max(0, $lastLine - $linesToShow);

    $file->seek($startLine);
    while (!$file->eof()) {
        echo htmlspecialchars($file->fgets());
    }
}

echo '</pre>';
