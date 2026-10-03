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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('seedling_type');
            $table->integer('quantity');
            $table->text('purpose');
            $table->string('contact_number', 20)->nullable();
            $table->date('requested_date');
            $table->decimal('price_per_unit', 10, 2)->default(5.00);
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Released'])->default('Pending');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            
            // Indexes
            $table->index('client_id');
            $table->index('status');
            $table->index('requested_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
