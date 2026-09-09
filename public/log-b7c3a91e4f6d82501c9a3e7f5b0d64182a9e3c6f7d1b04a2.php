<?php
// TEMPORARY log viewer. DELETE once no longer needed.
$path = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($path)) { echo 'No log file found.'; exit; }
$lines = 300;
$file = new SplFileObject($path, 'r');
$file->seek(PHP_INT_MAX);
$total = $file->key();
$file->seek(max(0, $total - $lines));
$out = '';
while (!$file->eof()) { $out .= $file->fgets(); }
echo '<pre>' . htmlspecialchars($out) . '</pre>';
