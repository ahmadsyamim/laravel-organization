<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferDeclined;
use CleaniqueCoders\LaravelOrganization\Mail\OwnershipTransferCompletedMail;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class DeclineOwnershipTransfer
{
    use AsAction;

    /**
     * Decline an ownership transfer request.
     *
     * @param  OwnershipTransferRequest  $transferRequest  The transfer request to decline
     * @param  User  $user  The user declining the request (must be the new owner)
     * @return OwnershipTransferRequest The declined transfer request
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function handle(OwnershipTransferRequest $transferRequest, User $user): OwnershipTransferRequest
    {
        // Validate that the user is the intended new owner
        if ($transferRequest->new_owner_id !== $user->id) {
            throw new InvalidArgumentException('Only the intended new owner can decline this transfer request.');
        }

        // Validate that the request is still valid
        if (! $transferRequest->isValid()) {
            if ($transferRequest->isExpired()) {
                throw new InvalidArgumentException('This transfer request has expired.');
            }
            if ($transferRequest->isAccepted()) {
                throw new InvalidArgumentException('This transfer request has already been accepted.');
            }
            if ($transferRequest->isDeclined()) {
                throw new InvalidArgumentException('This transfer request has already been declined.');
            }
            if ($transferRequest->isCancelled()) {
                throw new InvalidArgumentException('This transfer request has been cancelled.');
            }
            throw new InvalidArgumentException('This transfer request is no longer valid.');
        }

        // Decline the request
        $transferRequest->decline();

        // Dispatch the event
        OwnershipTransferDeclined::dispatch($transferRequest);

        // Send notification to the current owner
        $this->sendNotification($transferRequest);

        return $transferRequest;
    }

    /**
     * Decline a transfer request by token.
     */
    public function handleByToken(string $token, User $user): OwnershipTransferRequest
    {
        $transferRequest = OwnershipTransferRequest::where('token', $token)->first();

        if (! $transferRequest) {
            throw new InvalidArgumentException('Invalid transfer request token.');
        }

        return $this->handle($transferRequest, $user);
    }

    /**
     * Send notification to the current owner.
     */
    protected function sendNotification(OwnershipTransferRequest $transferRequest): void
    {
        $currentOwner = $transferRequest->currentOwner;
        $email = $currentOwner->getAttribute('email');

        if ($email) {
            Mail::to($email)->send(new OwnershipTransferCompletedMail($transferRequest, false));
        }
    }
}
