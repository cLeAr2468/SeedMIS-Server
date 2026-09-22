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
        // Check if admin already exists
        $existingAdmin = Admin::where('email', 'admin@seedmis.com')->first();

        if ($existingAdmin) {
            echo "ℹ️  Admin account already exists.\n";
            echo "   Email: admin@seedmis.com\n";
            return;
        }

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
