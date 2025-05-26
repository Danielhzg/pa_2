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
        Schema::create('reports', function (Blueprint $table) {
            $table->increments('id'); // Changed from id() to increments() to use int
            $table->integer('order_id')->unsigned()->nullable(); // Changed to unsigned integer
            $table->string('report_number')->unique();
            $table->string('report_type', 20)->default('sales')->comment('sales, inventory, customer, financial, custom');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('report_period_start')->nullable();
            $table->dateTime('report_period_end')->nullable();
            $table->json('report_data')->nullable()->comment('JSON data containing report information');
            $table->json('chart_data')->nullable()->comment('JSON data for charts/visualizations');
            $table->string('export_format')->nullable()->comment('PDF, EXCEL, CSV, etc.');
            $table->string('file_path')->nullable()->comment('Path to the generated report file if stored');
            $table->integer('admin_id')->unsigned()->nullable(); // Changed to unsigned integer
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable()->comment('daily, weekly, monthly, etc.');
            $table->text('recipients')->nullable()->comment('Email addresses for scheduled reports');
            $table->timestamps();
            
            // Indexes
            $table->index('report_type');
            $table->index('report_period_start');
            $table->index('report_period_end');
            $table->index('admin_id');
            $table->index('order_id');
            $table->index('created_at');
        });
        
        // Add foreign keys after table creation to prevent constraint issues
        Schema::table('reports', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
                
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
}; 