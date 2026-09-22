<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing admin if exists
        DB::table('admins')->where('email', 'admin@seedmis.com')->delete();

        // Create default admin with exact hash that works
        DB::table('admins')->insert([
            'name' => 'Admin',
            'email' => 'admin@seedmis.com',
            'password' => '$2y$12$LQv3c1yycL6UZP.rELQ8eOr5KdQqL8KJ5X9iVQB.FqZJLQKFRqTYq',
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Admin account created successfully!\n";
        echo "   Email: admin@seedmis.com\n";
        echo "   Password: admin123\n";
    }
}
