<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProvincesSeeder::class,
            FoundChildSeeder::class,
        ]);

        // Seed default Admin Account
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@timkiemtreem.vn'],
            [
                'full_name' => 'Hệ thống Admin',
                'phone' => '0905123456',
                'password' => \Illuminate\Support\Facades\Hash::make('Admin@123456'),
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );
    }
}
