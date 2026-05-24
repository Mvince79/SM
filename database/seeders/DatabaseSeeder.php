<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = \App\Models\User::firstOrCreate(
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

        $this->call([
            ProvincesSeeder::class,
            MissingChildSeeder::class,
            FoundChildSeeder::class,
        ]);
    }
}
