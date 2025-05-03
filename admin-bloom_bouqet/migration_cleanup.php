<?php

/**
 * Migration Cleanup Script for Bloom Bouquet (September 2024)
 * 
 * This script removes redundant and obsolete migration files after the migration reorganization.
 * Run this script with: php migration_cleanup.php
 */

// These core migrations should be kept
$coreSystemMigrations = [
    'database/migrations/0001_01_01_000001_create_cache_table.php',
    'database/migrations/0001_01_01_000002_create_jobs_table.php',
    'database/migrations/2025_03_15_062913_create_personal_access_tokens_table.php',
    
    // New consolidated migrations
    'database/migrations/2024_09_01_000000_create_core_schema.php',
    'database/migrations/2024_09_01_000001_create_ecommerce_schema.php',
    'database/migrations/2024_09_01_000002_create_communication_schema.php',
];

// Get all migration files
$migrationPath = __DIR__ . '/database/migrations/';
$allMigrations = glob($migrationPath . '*.php');

// Determine migrations to delete (all except those in the keep list)
$migrationsToDelete = [];
foreach ($allMigrations as $fullPath) {
    $relativePath = str_replace(__DIR__ . '/', '', $fullPath);
    if (!in_array($relativePath, $coreSystemMigrations) && $relativePath !== 'database/migrations/README.md') {
        $migrationsToDelete[] = $relativePath;
    }
}

echo "⚠️ Bloom Bouquet Migration Cleanup (September 2024) ⚠️\n";
echo "==============================================\n\n";
echo "This script will remove old migration files and keep only the new consolidated structure.\n";
echo "The files will be listed but NOT deleted yet.\n\n";

// Report on files
echo "Found " . count($migrationsToDelete) . " files to remove:\n";
foreach ($migrationsToDelete as $index => $file) {
    echo ($index + 1) . ". " . $file . "\n";
}

echo "\nThe following core migration files will be preserved:\n";
foreach ($coreSystemMigrations as $file) {
    echo "- " . $file . "\n";
}

// Ask for confirmation
echo "\nDo you want to proceed with deletion? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) != 'yes') {
    echo "Operation cancelled.\n";
    exit;
}

// Delete the files
$deleted = 0;
foreach ($migrationsToDelete as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath) && unlink($fullPath)) {
        echo "Deleted: " . $file . "\n";
        $deleted++;
    } else {
        echo "Failed to delete: " . $file . "\n";
    }
}

echo "\nDeletion completed. Removed " . $deleted . " files.\n";
echo "Next steps:\n";
echo "1. Run 'composer dump-autoload' to clear the class map\n";
echo "2. Run 'php artisan migrate:fresh' to apply the new migration structure\n";
echo "3. Make sure your models match the new database schema\n"; 