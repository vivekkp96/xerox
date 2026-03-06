<?php

namespace App\Providers;

use App\Models\Notification;

use App\Models\User;
use App\Providers\MailProvider;

class NotificationProvider
{
    /**
     * Create a notification record and optionally send email.
     *
     * @param array $data
     * @param bool $sendEmail
     * @return Notification
     */
    public static function create(array $data, bool $sendEmail = false): Notification
    {
        $notification = Notification::create($data);
        if ($sendEmail && isset($data['user_id'])) {
            $user = User::find($data['user_id']);
            if ($user && !empty($user->email)) {
                $subject = 'New Notification from ' . config('app.name', 'Company');
                $companyName = config('app.name', 'Company');
                $notificationMessage = $data['message'] ?? '';
                $message = "<div style='font-family: Arial, sans-serif; background: #f9f9f9; padding: 24px;'>"
                    . "<div style='max-width: 600px; margin: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #eee; padding: 32px;'>"
                    . "<h2 style='color: #4f46e5;'>$companyName Notification</h2>"
                    . "<p style='font-size: 1.1em; color: #222;'>Dear " . htmlspecialchars($user->fullname ?? $user->email) . ",</p>"
                    . "<p style='font-size: 1.1em; color: #222;'>$notificationMessage</p>"
                    . "<hr style='margin: 24px 0;'>"
                    . "<p style='color: #888; font-size: 0.95em;'>This is an automated message from $companyName. Please do not reply to this email.</p>"
                    . "</div>"
                    . "</div>";
                MailProvider::send($user->email, $subject, $message);
            }
        }
        return $notification;
    }
}
