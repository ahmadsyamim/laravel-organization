<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferCancelled;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Foundation\Auth\User;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class CancelOwnershipTransfer
{
    use AsAction;

    /**
     * Cancel an ownership transfer request.
     *
     * @param  OwnershipTransferRequest  $transferRequest  The transfer request to cancel
     * @param  User  $user  The user cancelling the request (must be the current owner)
     * @return OwnershipTransferRequest The cancelled transfer request
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function handle(OwnershipTransferRequest $transferRequest, User $user): OwnershipTransferRequest
    {
        // Validate that the user is the current owner
        if ($transferRequest->current_owner_id !== $user->id) {
            throw new InvalidArgumentException('Only the current owner can cancel this transfer request.');
        }

        // Validate that the request is still pending
        if (! $transferRequest->isPending()) {
            if ($transferRequest->isAccepted()) {
                throw new InvalidArgumentException('This transfer request has already been accepted and cannot be cancelled.');
            }
            if ($transferRequest->isDeclined()) {
                throw new InvalidArgumentException('This transfer request has already been declined.');
            }
            if ($transferRequest->isCancelled()) {
                throw new InvalidArgumentException('This transfer request has already been cancelled.');
            }
            throw new InvalidArgumentException('This transfer request cannot be cancelled.');
        }

        // Cancel the request
        $transferRequest->cancel();

        // Dispatch the event
        OwnershipTransferCancelled::dispatch($transferRequest);

        return $transferRequest;
    }
}
