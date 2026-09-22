<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOTP extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $userName;

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $userName = null)
    {
        $this->otp = $otp;
        $this->userName = $userName ?? 'User';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('SeedMIS - Password Reset OTP')
                    ->view('emails.password-reset-otp');
    }
}
