<?php

/**
 * Check Admins Table Structure Script
 * 
 * This script checks the admins table structure to ensure it has the required columns.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Checking admins table structure...\n\n";

if (Schema::hasTable('admins')) {
    echo "✓ Admins table exists\n\n";
    
    echo "Columns in admins table:\n";
    $columns = Schema::getColumnListing('admins');
    foreach ($columns as $column) {
        echo "- {$column}\n";
    }
    
    echo "\nChecking required columns:\n";
    $requiredColumns = ['id', 'username', 'email', 'password', 'role', 'is_active', 'created_at', 'updated_at'];
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columns)) {
            echo "✓ {$column} exists\n";
        } else {
            echo "✗ {$column} is missing\n";
        }
    }
    
    echo "\nSample data from admins table:\n";
    $admins = DB::table('admins')->limit(1)->get();
    if (count($admins) > 0) {
        $admin = $admins[0];
        // Don't show password for security
        unset($admin->password);
        print_r($admin);
    } else {
        echo "No admin records found.\n";
    }
    
} else {
    echo "✗ Admins table does not exist\n";
}

echo "\nCheck completed!\n"; 