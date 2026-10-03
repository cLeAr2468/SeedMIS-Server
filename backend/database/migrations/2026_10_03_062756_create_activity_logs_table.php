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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('user_type', ['admin', 'staff']); // Who performed the action
            $table->string('action'); // created, updated, deleted, approved, rejected, etc.
            $table->string('module'); // production, inventory, request, client, staff, target, profile
            $table->string('description'); // Detailed description of the action
            $table->json('details')->nullable(); // Additional JSON data (old/new values, etc.)
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'user_type']);
            $table->index('module');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
