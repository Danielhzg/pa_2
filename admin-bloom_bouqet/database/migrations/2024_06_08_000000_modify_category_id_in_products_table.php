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
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                // Check if the foreign key constraint exists before dropping it
                $foreignKeys = $this->listTableForeignKeys('products');
                if (in_array('products_category_id_foreign', $foreignKeys)) {
                    $table->dropForeign(['category_id']);
                }
                
                // Modify the column to be nullable with default null
                $table->foreignId('category_id')->nullable()->default(null)->change();
                
                // Add back the foreign key constraint
                if (Schema::hasTable('categories')) {
                    $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
                }
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
                // Check if the foreign key constraint exists before dropping it
                $foreignKeys = $this->listTableForeignKeys('products');
                if (in_array('products_category_id_foreign', $foreignKeys)) {
                    $table->dropForeign(['category_id']);
                }
                
                $table->foreignId('category_id')->nullable()->change();
                
                if (Schema::hasTable('categories')) {
                    $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Get the foreign key constraint names for the given table.
     *
     * @param string $table
     * @return array
     */
    private function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        $tableDetails = $conn->listTableDetails($table);
        $foreignKeys = [];

        foreach ($tableDetails->getForeignKeys() as $key) {
            $foreignKeys[] = $key->getName();
        }

        return $foreignKeys;
    }
};
