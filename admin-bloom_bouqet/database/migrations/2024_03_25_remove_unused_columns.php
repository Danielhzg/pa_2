<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before dropping them
            $columns = $this->getTableColumns('users');
            
            if (in_array('address', $columns)) {
                $table->dropColumn('address');
            }
            
            if (in_array('birth_date', $columns)) {
                $table->dropColumn('birth_date');
            }
            
            if (in_array('email_verified_at', $columns)) {
                $table->dropColumn('email_verified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
        });
    }
    
    /**
     * Get all columns from a table
     */
    protected function getTableColumns($table)
    {
        $columns = [];
        
        $columnData = DB::select(DB::raw('SHOW COLUMNS FROM ' . $table));
        
        foreach ($columnData as $column) {
            $columns[] = $column->Field;
        }
        
        return $columns;
    }
};
