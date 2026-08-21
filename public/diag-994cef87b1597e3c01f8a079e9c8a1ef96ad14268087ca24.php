<?php
// TEMPORARY diagnostic script. DELETE once no longer needed.
echo "<pre>";

$gitDir = __DIR__ . '/../.git';
echo ".git exists: " . var_export(is_dir($gitDir), true) . "\n";

if (is_dir($gitDir)) {
    $headContent = @file_get_contents($gitDir . '/HEAD');
    echo "HEAD: " . trim((string)$headContent) . "\n";

    if ($headContent && preg_match('/ref: (.+)/', $headContent, $m)) {
        $refPath = $gitDir . '/' . trim($m[1]);
        if (file_exists($refPath)) {
            echo "Resolved commit: " . trim(file_get_contents($refPath)) . "\n";
        } else {
            // maybe packed-refs
            $packed = @file_get_contents($gitDir . '/packed-refs');
            if ($packed && preg_match('/^([a-f0-9]+) ' . preg_quote(trim($m[1]), '/') . '$/m', $packed, $pm)) {
                echo "Resolved commit (packed-refs): " . $pm[1] . "\n";
            } else {
                echo "Could not resolve ref: " . trim($m[1]) . "\n";
            }
        }
    }
}

echo "\nKey file checks:\n";
$checks = [
    'app/View/Composers/ManagerLayoutComposer.php',
    'app/Services/JournalPoster.php',
    'app/Http/Controllers/LoanManager/ClientController.php',
    '.htaccess',
];
foreach ($checks as $rel) {
    $p = __DIR__ . '/../' . $rel;
    $exists = file_exists($p);
    $mtime = $exists ? date('Y-m-d H:i:s', filemtime($p)) : 'n/a';
    echo "  {$rel}: " . ($exists ? "EXISTS (mtime {$mtime})" : "MISSING") . "\n";
}

echo "</pre>";
