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
        Schema::table('inventories', function (Blueprint $table) {
            // Drop the old unique constraint on seedling_type alone
            $table->dropUnique(['seedling_type']);
            
            // Add new unique constraint on combination of seedling_type + classification
            // This allows same seedling type with different classifications
            $table->unique(['seedling_type', 'classification'], 'inventories_type_classification_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Drop the combined unique constraint
            $table->dropUnique('inventories_type_classification_unique');
            
            // Restore the old unique constraint on seedling_type alone
            $table->unique('seedling_type');
        });
    }
};
