<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

class PhoneNumberProvider extends ServiceProvider
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
     * Validate a phone number.
     *
     * @param string $number
     * @param string $region Default region code (e.g., 'IN' for India)
     * @return string|null Returns error message if invalid, null if valid
     */
    public static function validate(string $number, string $region = 'IN'): ?string
    {
        // Remove any non-digit characters for the repetitive check
        $cleanNumber = preg_replace('/\D/', '', $number);

        // Check if the number is composed of the same digit repeated (e.g., 1111111111)
        if (preg_match('/^(\d)\1+$/', $cleanNumber)) {
            return 'Phone number cannot consist of repetitive digits.';
        }

        // Use libphonenumber for robust validation if available
        if (class_exists(PhoneNumberUtil::class)) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $numberProto = $phoneUtil->parse($number, $region);
                if (!$phoneUtil->isValidNumber($numberProto)) {
                    return 'The phone number is invalid.';
                }
                return null;
            } catch (NumberParseException $e) {
                return 'The phone number format is invalid.';
            }
        }

        // Fallback: Check length (10-15 digits)
        if (strlen($cleanNumber) < 10 || strlen($cleanNumber) > 15) {
            return 'Phone number length must be between 10 and 15 digits.';
        }

        return null;
    }
}