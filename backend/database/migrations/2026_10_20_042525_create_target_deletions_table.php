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
        Schema::create('target_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_target_id'); // ID from the original target_settings table
            $table->integer('year');
            $table->decimal('target_seedlings', 15, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('deleted_by'); // Admin who deleted it
            $table->timestamp('deleted_at'); // When it was deleted
            $table->timestamps();
            
            // Store who deleted it
            $table->foreign('deleted_by')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_deletions');
    }
};
