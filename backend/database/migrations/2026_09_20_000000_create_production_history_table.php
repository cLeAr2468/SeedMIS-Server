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
        Schema::create('production_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_id');
            $table->string('batch_id');
            $table->string('seedling_type');
            $table->string('classification')->nullable();
            $table->enum('action_type', ['created', 'stage_update', 'quantity_update', 'edited', 'transferred', 'deleted']);
            $table->string('previous_stage')->nullable();
            $table->string('new_stage')->nullable();
            $table->integer('previous_quantity')->nullable();
            $table->integer('new_quantity')->nullable();
            $table->string('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            
            // Indexes for better query performance
            $table->index('production_id');
            $table->index('batch_id');
            $table->index('action_type');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_history');
    }
};
