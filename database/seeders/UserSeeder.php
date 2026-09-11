<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+1234567890',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+1234567891',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+1234567892',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Carlos López',
            'email' => 'carlos@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+1234567893',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'María García',
            'email' => 'maria@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+1234567894',
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }
}
