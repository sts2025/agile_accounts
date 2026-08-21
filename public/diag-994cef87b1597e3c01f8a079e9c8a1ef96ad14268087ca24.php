<?php
// TEMPORARY diagnostic script. DELETE once no longer needed.
echo "<pre>";

$dir = __DIR__ . '/../app/View';
echo "Contents of app/View/:\n";
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $dir . '/' . $f;
        echo "  - " . $f . (is_dir($full) ? '/' : '') . "\n";
        if (is_dir($full)) {
            foreach (scandir($full) as $f2) {
                if ($f2 === '.' || $f2 === '..') continue;
                echo "      - " . $f2 . "\n";
            }
        }
    }
} else {
    echo "  (app/View does not exist)\n";
}

echo "\nshell_exec git ls-tree (if available):\n";
if (function_exists('shell_exec')) {
    $out = @shell_exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && git ls-tree -r --name-only HEAD 2>&1 | grep -i composer');
    echo $out === null ? "  (shell_exec returned null / disabled)\n" : ($out ?: "  (no matches for 'composer' in HEAD tree)\n");

    $log = @shell_exec('cd ' . escapeshellarg(__DIR__ . '/..') . ' && git log --oneline -5 2>&1');
    echo "\nLast 5 commits on deployed HEAD:\n" . ($log ?: "  (none / shell_exec disabled)\n");
} else {
    echo "  (shell_exec disabled on this host)\n";
}

echo "</pre>";
