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
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('upgraded_to_client_id')->nullable()->after('province');
            $table->boolean('is_active')->default(true)->after('upgraded_to_client_id');
            $table->timestamp('upgraded_at')->nullable()->after('is_active');
            
            // Foreign key to clients table
            $table->foreign('upgraded_to_client_id')->references('id')->on('clients')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['upgraded_to_client_id']);
            $table->dropColumn(['upgraded_to_client_id', 'is_active', 'upgraded_at']);
        });
    }
};
