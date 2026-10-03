<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $request;
    public $status;
    public $clientName;

    /**
     * Create a new message instance.
     */
    public function __construct($request, $status)
    {
        $this->request = $request;
        $this->status = $status;
        $this->clientName = $request->client 
            ? trim("{$request->client->first_name} {$request->client->middle_name} {$request->client->last_name}")
            : 'Client';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->status) {
            'Approved' => 'Your Seedling Request Has Been Approved',
            'Rejected' => 'Update on Your Seedling Request',
            'Released' => 'Your Seedlings Are Ready for Pickup',
            default => 'Request Status Update',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.request-status-changed',
            with: [
                'request' => $this->request,
                'status' => $this->status,
                'clientName' => $this->clientName,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
