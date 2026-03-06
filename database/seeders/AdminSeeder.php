<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Constants\AppConstants;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Using insert to bypass the 'hashed' cast on the model since we have a raw hash
        Admin::insert([
            [
                'email' => 'admin@gmail.comm',
                'password' => '$2y$12$7aJfanGqYSFGpuXZPy0O2uWS0lG3jwe7ttWI2SJWeUMabfzfSo0v.',
                'role' => AppConstants::ADMIN_ROLE_ADMIN,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'superadmin@gmail.comm',
                'password' => '$2y$12$7aJfanGqYSFGpuXZPy0O2uWS0lG3jwe7ttWI2SJWeUMabfzfSo0v.',
                'role' => AppConstants::ADMIN_ROLE_SUPER_ADMIN,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}