<?php

namespace CleaniqueCoders\LaravelOrganization\Mail;

use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OwnershipTransferRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public OwnershipTransferRequest $transferRequest;

    public ?string $customAcceptUrl;

    public ?string $customDeclineUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        OwnershipTransferRequest $transferRequest,
        ?string $acceptUrl = null,
        ?string $declineUrl = null,
    ) {
        $this->transferRequest = $transferRequest;
        $this->customAcceptUrl = $acceptUrl;
        $this->customDeclineUrl = $declineUrl;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject("Ownership Transfer Request for {$this->transferRequest->organization->name}")
            ->view('org::emails.ownership-transfer-request')
            ->with([
                'transferRequest' => $this->transferRequest,
                'organization' => $this->transferRequest->organization,
                'currentOwner' => $this->transferRequest->currentOwner,
                'newOwner' => $this->transferRequest->newOwner,
                'personalMessage' => $this->transferRequest->message,
                'expiresAt' => $this->transferRequest->expires_at,
                'acceptUrl' => $this->getAcceptUrl(),
                'declineUrl' => $this->getDeclineUrl(),
            ]);
    }

    /**
     * Get the accept URL.
     */
    public function getAcceptUrl(): string
    {
        if ($this->customAcceptUrl) {
            return $this->customAcceptUrl;
        }

        $token = $this->transferRequest->token;
        $baseUrl = config('app.url', 'http://localhost');

        return "{$baseUrl}/organization/transfer/{$token}/accept";
    }

    /**
     * Get the decline URL.
     */
    public function getDeclineUrl(): string
    {
        if ($this->customDeclineUrl) {
            return $this->customDeclineUrl;
        }

        $token = $this->transferRequest->token;
        $baseUrl = config('app.url', 'http://localhost');

        return "{$baseUrl}/organization/transfer/{$token}/decline";
    }
}
