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
        Schema::table('requests', function (Blueprint $table) {
            // Add requester type to distinguish between client and customer
            $table->enum('requester_type', ['client', 'customer'])->default('client')->after('client_id');
            
            // Add customer_id field for customer requests
            $table->unsignedBigInteger('customer_id')->nullable()->after('requester_type');
            
            // Make client_id nullable since we now have customer_id option
            $table->unsignedBigInteger('client_id')->nullable()->change();
            
            // Foreign key to customers table
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['requester_type', 'customer_id']);
            
            // Restore client_id as not nullable
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
        });
    }
};
