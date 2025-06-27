<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the admin already exists
        if (!User::where('email', 'admin@agileaccounts.com')->exists()) {
            
            // Create the Super Admin User
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@agileaccounts.com',
                'password' => Hash::make('password123'), // Use a strong, secure password!
                'user_type' => 'super_admin',
            ]);

        }
    }
}