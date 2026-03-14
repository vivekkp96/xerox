<?php

namespace Database\Seeders;

use App\Models\Report;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Report::create([
            'name' => 'User Report',
            'reports' => [
                'users peak active list' => (object) [],
                'performance' => (object) [],
            ],
        ]);
    }
}