<?php

/**
 * TEMPORARY diagnostic script. DELETE once no longer needed.
 */

require __DIR__ . '/../vendor/autoload.php';

$path = __DIR__ . '/../app/View/Composers/ManagerLayoutComposer.php';

echo "<pre>";
echo "file_exists: " . var_export(file_exists($path), true) . "\n";
echo "realpath: " . var_export(realpath($path), true) . "\n\n";

$dir = __DIR__ . '/../app/View/Composers';
echo "Directory listing of app/View/Composers:\n";
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        echo "  - " . $f . "\n";
    }
} else {
    echo "  (directory does not exist)\n";
}

echo "\nclass_exists check (after autoload): " . var_export(class_exists('App\\View\\Composers\\ManagerLayoutComposer'), true) . "\n";

echo "\ncomposer.json autoload psr-4:\n";
$composerJson = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
print_r($composerJson['autoload']['psr-4'] ?? []);

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\nAfter Laravel bootstrap, class_exists: " . var_export(class_exists('App\\View\\Composers\\ManagerLayoutComposer'), true) . "\n";

echo "</pre>";
