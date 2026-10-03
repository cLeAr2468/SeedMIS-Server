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
        // Step 1: Temporarily allow both values in enum
        DB::statement("ALTER TABLE productions MODIFY COLUMN classification ENUM('Crafted', 'Grafted', 'Seedling') DEFAULT 'Seedling'");
        DB::statement("ALTER TABLE inventories MODIFY COLUMN classification ENUM('Crafted', 'Grafted', 'Seedling')");
        
        // Step 2: Update existing 'Crafted' values to 'Grafted'
        DB::table('productions')
            ->where('classification', 'Crafted')
            ->update(['classification' => 'Grafted']);
        
        DB::table('inventories')
            ->where('classification', 'Crafted')
            ->update(['classification' => 'Grafted']);
        
        // Step 3: Remove 'Crafted' from enum, keeping only 'Grafted' and 'Seedling'
        DB::statement("ALTER TABLE productions MODIFY COLUMN classification ENUM('Grafted', 'Seedling') DEFAULT 'Seedling'");
        DB::statement("ALTER TABLE inventories MODIFY COLUMN classification ENUM('Grafted', 'Seedling')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update 'Grafted' back to 'Crafted' in productions table
        DB::table('productions')
            ->where('classification', 'Grafted')
            ->update(['classification' => 'Crafted']);
        
        // Update 'Grafted' back to 'Crafted' in inventories table
        DB::table('inventories')
            ->where('classification', 'Grafted')
            ->update(['classification' => 'Crafted']);
        
        // Alter productions table enum back
        DB::statement("ALTER TABLE productions MODIFY COLUMN classification ENUM('Crafted', 'Seedling') DEFAULT 'Seedling'");
        
        // Alter inventories table enum back
        DB::statement("ALTER TABLE inventories MODIFY COLUMN classification ENUM('Crafted', 'Seedling')");
    }
};
