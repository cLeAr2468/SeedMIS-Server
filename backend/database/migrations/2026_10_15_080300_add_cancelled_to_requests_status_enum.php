<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the status enum to include 'Cancelled'
        DB::statement("ALTER TABLE `requests` MODIFY COLUMN `status` ENUM('Pending', 'Approved', 'Rejected', 'Released', 'Cancelled') NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'Cancelled' from the enum (only if no records use it)
        DB::statement("ALTER TABLE `requests` MODIFY COLUMN `status` ENUM('Pending', 'Approved', 'Rejected', 'Released') NOT NULL DEFAULT 'Pending'");
    }
};
