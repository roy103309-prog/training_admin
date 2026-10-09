<?php
// Script to update all API files to use tenant-config.php instead of config.php

$apiDir = __DIR__;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($apiDir),
    RecursiveIteratorIterator::SELF_FIRST
);

$updatedCount = 0;
$errorFiles = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();

        // Skip this script itself
        if (basename($filePath) === 'update-to-tenant-config.php' ||
            basename($filePath) === 'tenant-config.php' ||
            basename($filePath) === 'config.php') {
            continue;
        }

        $content = file_get_contents($filePath);

        // Replace config.php with tenant-config.php, but skip if already using tenant-config
        if (strpos($content, "require_once '../config.php'") !== false &&
            strpos($content, 'tenant-config.php') === false) {

            $newContent = str_replace(
                "require_once '../config.php'",
                "require_once '../tenant-config.php'",
                $content
            );

            // Also replace getDbConnection() with getTenantConnection()
            $newContent = str_replace(
                'getDbConnection()',
                'getTenantConnection()',
                $newContent
            );

            if (file_put_contents($filePath, $newContent)) {
                $updatedCount++;
                echo "Updated: " . $filePath . "\n";
            } else {
                $errorFiles[] = $filePath;
            }
        }
    }
}

echo "\n\n=== Summary ===\n";
echo "Files updated: $updatedCount\n";
if (!empty($errorFiles)) {
    echo "Errors in:\n";
    foreach ($errorFiles as $file) {
        echo "  - $file\n";
    }
}
echo "\nDone!\n";
