<?php

namespace App\Providers;

class PrintConfigurationProvider
{
    public static function getValidOrientationIds(): array
    {
        $path = resource_path('js/data/print-orientations.json');

        if (!file_exists($path)) {
            return [];
        }

        $json = json_decode(file_get_contents($path), true);

        return is_array($json) ? array_column($json, 'id') : [];
    }
}