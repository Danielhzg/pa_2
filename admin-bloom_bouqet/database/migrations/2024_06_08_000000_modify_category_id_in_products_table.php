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
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Check if the foreign key exists before dropping it
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'category_id' AND TABLE_SCHEMA = DATABASE()");
                if (!empty($foreignKeys)) {
                    $table->dropForeign(['category_id']);
                }

                // Modify the column to be nullable with default null
                $table->foreignId('category_id')->nullable()->default(null)->change();

                // Add back the foreign key constraint
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Check if the foreign key exists before dropping it
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'category_id' AND TABLE_SCHEMA = DATABASE()");
                if (!empty($foreignKeys)) {
                    $table->dropForeign(['category_id']);
                }

                // Revert the column to its original state
                $table->foreignId('category_id')->nullable(false)->change();

                // Add back the foreign key constraint
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            });
        }
    }
};
