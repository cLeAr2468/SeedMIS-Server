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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique();
            $table->string('seedling_type');
            $table->string('scientific_name')->nullable();
            $table->enum('classification', ['Crafted', 'Seedling'])->default('Seedling');
            $table->date('date_sown');
            $table->date('expected_ready');
            $table->integer('quantity_sown');
            $table->integer('current_quantity');
            $table->decimal('survivability', 5, 2)->nullable();
            $table->enum('stage', ['Germination', 'Seedling', 'Hardening', 'Ready'])->default('Germination');
            $table->string('location');
            $table->string('assigned_staff')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
