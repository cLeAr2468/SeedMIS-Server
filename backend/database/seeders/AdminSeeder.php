<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing admin if exists
        Admin::where('email', 'admin@seedmis.com')->delete();

        // Create default admin - password will be auto-hashed by model
        Admin::create([
            'name' => 'Admin',
            'email' => 'admin@seedmis.com',
            'password' => 'admin123',  // Plain text - auto-hashed by model
            'role' => 'admin',
        ]);

        echo "✅ Admin account created successfully!\n";
        echo "   Email: admin@seedmis.com\n";
        echo "   Password: admin123\n";
    }
}
