<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove unused remember_token columns from user tables.
     * These are not being used since we're using session-based authentication
     * and API token authentication (for clients), not "Remember Me" functionality.
     */
    public function up(): void
    {
        // Remove remember_token from admins
        if (Schema::hasColumn('admins', 'remember_token')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }

        // Remove remember_token from staff
        if (Schema::hasColumn('staff', 'remember_token')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }

        // Remove remember_token from clients
        if (Schema::hasColumn('clients', 'remember_token')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }

        // Remove remember_token from customers
        if (Schema::hasColumn('customers', 'remember_token')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back remember_token columns
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'remember_token')) {
                $table->rememberToken();
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'remember_token')) {
                $table->rememberToken();
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'remember_token')) {
                $table->rememberToken();
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }
};
