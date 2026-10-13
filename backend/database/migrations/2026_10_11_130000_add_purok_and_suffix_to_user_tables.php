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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('purok')->nullable()->after('region');
            $table->string('suffix')->nullable()->after('last_name');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('purok')->nullable()->after('region');
            $table->string('suffix')->nullable()->after('last_name');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->string('purok')->nullable()->after('region');
            $table->string('suffix')->nullable()->after('last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['purok', 'suffix']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['purok', 'suffix']);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['purok', 'suffix']);
        });
    }
};
