<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegisterationMail extends Mailable
{

    use Queueable, SerializesModels;

    public $otp;
    public $validity;

    public function __construct($otp, $validity)
    {
        $this->otp = $otp;
        $this->validity = $validity;
    }

    public function build()
    {
        return $this->subject('Verify your Email')
            ->view('emails.registeration');
    }
}
