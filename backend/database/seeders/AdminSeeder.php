<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        // Create default admin
        Admin::create([
            'name' => 'Admin',
            'email' => 'admin@seedmis.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        echo "✅ Admin account created successfully!\n";
        echo "   Email: admin@seedmis.com\n";
        echo "   Password: admin123\n";
    }
}
