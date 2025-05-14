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
            $table->id();
            $table->string('report_number')->unique();
            $table->enum('report_type', ['sales', 'inventory', 'customer', 'financial', 'custom'])->default('sales');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('report_period_start')->nullable();
            $table->dateTime('report_period_end')->nullable();
            $table->json('report_data')->nullable()->comment('JSON data containing report information');
            $table->json('chart_data')->nullable()->comment('JSON data for charts/visualizations');
            $table->string('export_format')->nullable()->comment('PDF, EXCEL, CSV, etc.');
            $table->string('file_path')->nullable()->comment('Path to the generated report file if stored');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable()->comment('daily, weekly, monthly, etc.');
            $table->text('recipients')->nullable()->comment('Email addresses for scheduled reports');
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('report_type');
            $table->index('report_period_start');
            $table->index('report_period_end');
            $table->index('admin_id');
            $table->index('created_at');
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