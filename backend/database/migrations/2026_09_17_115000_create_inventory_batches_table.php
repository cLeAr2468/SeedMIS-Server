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
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->string('batch_number')->unique(); // e.g., BTH-ML-001
            $table->string('production_batch_id')->nullable(); // Original production batch_id
            $table->integer('quantity'); // Quantity in this batch
            $table->date('date_received'); // When transferred from production
            $table->date('date_sown')->nullable(); // From production record
            $table->date('expected_ready')->nullable(); // From production record
            $table->string('location')->nullable(); // Greenhouse/area location
            $table->enum('quality_status', ['Excellent', 'Good', 'Fair', 'Poor'])->default('Good');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
