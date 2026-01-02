<?php

namespace CleaniqueCoders\LaravelOrganization\Mail;

use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnershipTransferCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  bool  $accepted  Whether the transfer was accepted (true) or declined (false)
     */
    public function __construct(
        public OwnershipTransferRequest $transferRequest,
        public bool $accepted = true,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->accepted ? 'Accepted' : 'Declined';

        return new Envelope(
            subject: "Ownership Transfer {$status} - {$this->transferRequest->organization->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'org::emails.ownership-transfer-completed',
            with: [
                'transferRequest' => $this->transferRequest,
                'organization' => $this->transferRequest->organization,
                'currentOwner' => $this->transferRequest->currentOwner,
                'newOwner' => $this->transferRequest->newOwner,
                'accepted' => $this->accepted,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
