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
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan field full_name setelah username jika belum ada
            if (!Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name')->after('username');
            }
            
            // Tambahkan field phone setelah email jika belum ada
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->after('email');
            }
            
            // Tambahkan field address setelah phone jika belum ada
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            
            // Tambahkan field birth_date setelah address jika belum ada
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('users', 'full_name')) {
                $table->dropColumn('full_name');
            }
            
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
            
            if (Schema::hasColumn('users', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
        });
    }
};