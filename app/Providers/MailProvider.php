<?php

namespace App\Providers;

use PDO;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;

class MailProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}

    /**
     * Send an email.
     *
     * @param string $to
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public static function send(string $to, string $subject, string $message): bool
    {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');
        $from = config('mail.from.address');

        try {
            $socket = fsockopen($host, $port, $errno, $errstr, 10);
            if (!$socket) {
                error_log("SMTP Connection failed: $errstr ($errno)");
                return false;
            }

            $read = function () use ($socket) {
                $s = '';
                while ($str = fgets($socket, 515)) {
                    $s .= $str;
                    if (substr($str, 3, 1) == ' ') break;
                }
                return $s;
            };

            $send = function ($cmd) use ($socket) {
                fputs($socket, $cmd . "\r\n");
            };

            $read(); // Server banner
            $send("EHLO " . gethostname());
            $read();

            // Handle STARTTLS for port 587
            if ($port == 587) {
                $send("STARTTLS");
                $read();
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $send("EHLO " . gethostname());
                $read();
            }

            if (!empty($username)) {
                $send("AUTH LOGIN");
                $read();
                $send(base64_encode($username));
                $read();
                $send(base64_encode($password));
                $read();
            }

            $send("MAIL FROM: <$from>");
            $read();
            $send("RCPT TO: <$to>");
            $read();
            $send("DATA");
            $read();

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $from\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";

            $send($headers . "\r\n" . $message . "\r\n.");
            $result = $read();

            $send("QUIT");
            fclose($socket);

            return strpos($result, '250') !== false;
        } catch (\Exception $e) {
            error_log("Mail send failed: " . $e->getMessage());
            return false;
        }
    }

    public static function sendByFacade(string $to, string $subject, string $message): bool
    {
        try {
            Mail::raw($message, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            error_log("Mail send failed: " . $e->getMessage());
            return false;
        }
    }
}
