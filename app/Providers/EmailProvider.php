<?php

namespace App\Providers;

use PDO;
use Illuminate\Support\ServiceProvider;
use App\Constants\AppConstants;
use App\Models\PreUser;
use App\Models\PreUserOtp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegisterationMail;

class EmailProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}

    /**
     * Validate an email address.
     *
     * @param string $email
     * @return bool
     */
    public static function validate(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function handleVerifyEmail(string $email)
    {
        $otp = RandomStringProvider::generateOtp();

        $mail = new RegisterationMail($otp, AppConstants::OTP_VALIDITY_MINUTES);
        $html = $mail->render();
        MailProvider::send($email, $mail->subject, $html);

        $preUser = PreUser::where('email', $email)->first();
        if ($preUser) {
            PreUserOtp::create([
                'pre_user_id' => $preUser->id,
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(AppConstants::OTP_VALIDITY_MINUTES),
            ]);
        }
    }
}
