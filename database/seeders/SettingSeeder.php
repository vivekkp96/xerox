<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Using insert to bypass model events and mass assignment protection.
        Setting::insert([
            [
                'name' => 'upi_id',
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'lamination_amount',
                'value' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}