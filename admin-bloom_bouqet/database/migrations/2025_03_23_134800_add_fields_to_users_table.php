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
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->after('email');
            }

            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }

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
            // drop only if columns exist
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
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
