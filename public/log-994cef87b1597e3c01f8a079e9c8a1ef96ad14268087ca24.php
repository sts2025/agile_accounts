<?php

/**
 * TEMPORARY log viewer for hosts without SSH/CLI access.
 * Prints the tail of storage/logs/laravel.log. Gated only by this
 * unguessable filename. DELETE once no longer needed.
 */

$path = __DIR__ . '/../storage/logs/laravel.log';

if (!file_exists($path)) {
    echo 'No log file found at ' . htmlspecialchars($path);
    exit;
}

$lines = 200;
$file = new SplFileObject($path, 'r');
$file->seek(PHP_INT_MAX);
$totalLines = $file->key();

$startLine = max(0, $totalLines - $lines);
$file->seek($startLine);

$out = '';
while (!$file->eof()) {
    $out .= $file->fgets();
}

echo '<pre>' . htmlspecialchars($out) . '</pre>';
