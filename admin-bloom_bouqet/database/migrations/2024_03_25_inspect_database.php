<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all columns from the users table
        $columns = DB::select('SHOW COLUMNS FROM users');
        
        // Log the columns for inspection
        $columnNames = array_map(function ($column) {
            return $column->Field;
        }, $columns);
        
        Log::info('Current users table columns:', $columnNames);
        
        // No actual changes in this migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes to reverse
    }
};
