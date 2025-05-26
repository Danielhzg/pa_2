<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Verifying database column types...\n\n";

$tables = [
    'users', 'admins', 'categories', 'products', 'favorites', 
    'carousels', 'orders', 'carts', 'reports', 'chat_messages', 
    'sessions', 'personal_access_tokens', 'notifications'
];

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "Table {$table} does not exist\n";
        continue;
    }
    
    echo "Table: {$table}\n";
    
    // Get column information
    $columns = DB::select("SHOW COLUMNS FROM {$table}");
    
    foreach ($columns as $column) {
        if ($column->Field === 'id' || strpos($column->Field, '_id') !== false) {
            echo "  - {$column->Field}: {$column->Type}";
            
            // Check if the column is using the proper type (int instead of bigint)
            if (strpos($column->Type, 'bigint') !== false) {
                echo " - WARNING: Using bigint instead of int";
            } else if (strpos($column->Type, 'int') !== false) {
                echo " - OK";
            }
            
            echo "\n";
        }
    }
    
    echo "\n";
}

echo "\nVerification complete.\n"; 