<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FileValidationProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    public static function validate(string $filename): bool
    {
        return strlen($filename) <= 70;
    }

    public static function validatePages(int $docTotalPages, string $printPages): bool
    {
        if (strtolower($printPages) === 'all') {
            return true;
        }

        $totalPrintPages = 0;
        $ranges = explode(',', $printPages);

        foreach ($ranges as $range) {
            $range = trim($range);
            if (strpos($range, '-') !== false) {
                $parts = explode('-', $range);
                if (count($parts) === 2) {
                    $start = (int)$parts[0];
                    $end = (int)$parts[1];
                    $totalPrintPages += max(0, $end - $start + 1);
                }
            } elseif (is_numeric($range)) {
                $totalPrintPages++;
            }
        }

        return $docTotalPages >= $totalPrintPages;
    }
}