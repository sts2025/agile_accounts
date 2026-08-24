<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre>';

echo "Migration files present on server:\n";
$files = glob(__DIR__ . '/../database/migrations/*.php');
sort($files);
foreach ($files as $f) {
    if (str_contains($f, '2026_08_2')) {
        echo "  " . basename($f) . "\n";
    }
}

echo "\nmigrations table contents (last 15):\n";
$rows = Illuminate\Support\Facades\DB::table('migrations')->orderByDesc('id')->limit(15)->get();
foreach ($rows as $row) {
    echo "  [{$row->batch}] {$row->migration}\n";
}

echo "\nphp artisan migrate:status\n";
Illuminate\Support\Facades\Artisan::call('migrate:status');
echo Illuminate\Support\Facades\Artisan::output();

echo '</pre>';
