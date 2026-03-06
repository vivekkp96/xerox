<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RandomStringProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Generate a 6 digit OTP.
     *
     * @return string
     */
    public static function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
