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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('seedling_type')->unique();
            $table->enum('classification', ['Crafted', 'Seedling']);
            $table->integer('available_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->decimal('price_per_unit', 10, 2);
            $table->string('unit')->default('pieces');
            $table->integer('min_stock_level')->default(0);
            $table->string('location')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
