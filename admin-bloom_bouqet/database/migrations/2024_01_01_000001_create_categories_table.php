<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Insert default categories
        DB::table('categories')->insert([
            ['name' => 'Wisuda'],
            ['name' => 'Makanan'],
            ['name' => 'Money'],
            ['name' => 'Hampers'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
