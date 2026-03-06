<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrintOrientation;

class PrintOrientationSeeder extends Seeder
{
    public function run(): void
    {
        $orientations = [
            PrintOrientation::PORTRAIT => '<svg width="32" height="40" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="40" rx="4" fill="#2563eb"/></svg>',
            PrintOrientation::LANDSCAPE => '<svg width="40" height="32" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="32" rx="4" fill="#2563eb"/></svg>',
        ];
        foreach ($orientations as $name => $svg) {
            PrintOrientation::updateOrCreate(
                ['name' => $name],
                ['svg' => $svg]
            );
        }
    }
}
