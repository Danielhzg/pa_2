<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make sure required fields are NOT NULL
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
            
            if (Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable(false)->change();
            }
            
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable(false)->change();
            }
            
            if (Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable(false)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes needed for rollback
    }
};
