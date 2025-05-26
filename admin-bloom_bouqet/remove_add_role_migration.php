<?php

/**
 * Remove Add Role Migration Script
 * 
 * This script removes the add_role_to_admins_table migration from the migrations table
 * since the columns are already included in the original migration.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "Starting migration cleanup...\n";

// Step 1: Check if the migration exists in the database
$migrationName = '2023_10_03_000001_add_role_to_admins_table';
$migration = DB::table('migrations')->where('migration', $migrationName)->first();

if ($migration) {
    echo "Found migration '{$migrationName}' in the database. Removing...\n";
    
    // Remove from the migrations table
    DB::table('migrations')->where('migration', $migrationName)->delete();
    echo "✓ Migration removed from the migrations table\n";
    
    // Delete the migration file
    $migrationFile = __DIR__ . '/database/migrations/' . $migrationName . '.php';
    if (File::exists($migrationFile)) {
        File::delete($migrationFile);
        echo "✓ Migration file deleted\n";
    } else {
        echo "Migration file not found at {$migrationFile}\n";
    }
} else {
    echo "Migration '{$migrationName}' not found in the database\n";
}

echo "\nMigration cleanup completed!\n";
echo "The add_role migration has been removed, as these columns are already in the original admins table migration.\n"; 