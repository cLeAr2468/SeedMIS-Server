<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountUpgraded extends Mailable
{
    use Queueable, SerializesModels;

    public $client;
    public $tempPassword;
    public $customerName;

    /**
     * Create a new message instance.
     */
    public function __construct(Client $client, $tempPassword)
    {
        $this->client = $client;
        $this->tempPassword = $tempPassword;
        $this->customerName = trim($client->first_name . ' ' . $client->last_name);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your SeedMIS Account Has Been Activated')
                    ->view('emails.account-upgraded')
                    ->with([
                        'clientName' => $this->customerName,
                        'clientId' => $this->client->client_id,
                        'email' => $this->client->email,
                        'tempPassword' => $this->tempPassword,
                        'loginUrl' => config('app.frontend_url', 'http://localhost:5173') . '/login',
                    ]);
    }
}
