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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->enum('target_type', ['annual_production', 'monthly_distribution', 'revenue', 'seedling_type']);
            $table->string('seedling_type')->nullable(); // For seedling_type targets
            $table->decimal('target_value', 12, 2);
            $table->string('period', 50); // e.g., '2026', '2026-09'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['target_type', 'period']);
            $table->index(['seedling_type', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
