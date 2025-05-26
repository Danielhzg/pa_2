<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "Checking database migration status...\n\n";

// Check if migrations table exists
if (!Schema::hasTable('migrations')) {
    echo "Migrations table does not exist. Run 'php artisan migrate' first.\n";
    exit(1);
}

// Get the list of migrations from the file system
$migrationFiles = glob(__DIR__ . '/migrations/*.php');
$migrationFileNames = array_map(function($path) {
    return basename($path, '.php');
}, $migrationFiles);

// Get the list of migrations from the database
$migrationsInDb = DB::table('migrations')->get();
$migratedNames = $migrationsInDb->pluck('migration')->toArray();

// Check which migrations are not in the database
$notMigrated = array_diff($migrationFileNames, $migratedNames);

echo "Migration Status:\n";
echo "----------------\n";
echo "Total migration files: " . count($migrationFileNames) . "\n";
echo "Total migrated: " . count($migratedNames) . "\n";
echo "Not migrated: " . count($notMigrated) . "\n\n";

if (count($notMigrated) > 0) {
    echo "The following migrations are not in the database:\n";
    foreach ($notMigrated as $migration) {
        echo "  - {$migration}\n";
    }
    echo "\n";
}

// Check for inconsistencies in the database
$tables = DB::select("SHOW TABLES");
$tableColumn = "Tables_in_" . env('DB_DATABASE');
$tableNames = array_map(function($table) use ($tableColumn) {
    return $table->$tableColumn;
}, $tables);

echo "Table Analysis:\n";
echo "--------------\n";
echo "Total tables: " . count($tableNames) . "\n\n";

// Check primary key types
echo "Primary Key Types:\n";
foreach ($tableNames as $table) {
    if (in_array($table, ['migrations', 'password_reset_tokens', 'failed_jobs', 'password_resets'])) {
        continue;
    }
    
    $columns = DB::select("SHOW COLUMNS FROM {$table}");
    foreach ($columns as $column) {
        if ($column->Field === 'id' && $column->Key === 'PRI') {
            $idType = $column->Type;
            $status = (strpos($idType, 'int(') !== false && strpos($idType, 'bigint') === false)
                ? 'OK'
                : 'WARNING: Not using int';
            
            echo "  - {$table}.id: {$idType} - {$status}\n";
        }
    }
}

echo "\n";

// Check foreign key types
echo "Foreign Key Consistency:\n";
$foreignKeys = DB::select("
    SELECT
        table_name,
        column_name,
        referenced_table_name,
        referenced_column_name
    FROM
        information_schema.key_column_usage
    WHERE
        referenced_table_name IS NOT NULL
        AND table_schema = DATABASE()
");

foreach ($foreignKeys as $fk) {
    $sourceColumn = DB::select("SHOW COLUMNS FROM {$fk->table_name} WHERE Field = '{$fk->column_name}'")[0];
    $targetColumn = DB::select("SHOW COLUMNS FROM {$fk->referenced_table_name} WHERE Field = '{$fk->referenced_column_name}'")[0];
    
    $sourceType = $sourceColumn->Type;
    $targetType = $targetColumn->Type;
    
    $status = ($sourceType === $targetType) ? 'OK' : 'MISMATCH';
    
    echo "  - {$fk->table_name}.{$fk->column_name} ({$sourceType}) -> {$fk->referenced_table_name}.{$fk->referenced_column_name} ({$targetType}): {$status}\n";
}

echo "\nCheck complete.\n";

// If all is fine, provide instructions for fresh migrations
echo "\nTo perform a fresh migration (WARNING: This will delete all data):\n";
echo "php artisan migrate:fresh --seed\n";
echo "\nTo run the pending migrations without losing data:\n";
echo "php artisan migrate\n"; 