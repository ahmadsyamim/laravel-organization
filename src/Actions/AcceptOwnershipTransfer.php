<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferAccepted;
use CleaniqueCoders\LaravelOrganization\Mail\OwnershipTransferCompletedMail;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class AcceptOwnershipTransfer
{
    use AsAction;

    /**
     * Accept an ownership transfer request.
     *
     * @param  OwnershipTransferRequest  $transferRequest  The transfer request to accept
     * @param  User  $user  The user accepting the request (must be the new owner)
     * @return Organization The organization with transferred ownership
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function handle(OwnershipTransferRequest $transferRequest, User $user): Organization
    {
        // Validate that the user is the intended new owner
        if ($transferRequest->new_owner_id !== $user->id) {
            throw new InvalidArgumentException('Only the intended new owner can accept this transfer request.');
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
                throw new InvalidArgumentException('This transfer request has been declined.');
            }
            if ($transferRequest->isCancelled()) {
                throw new InvalidArgumentException('This transfer request has been cancelled.');
            }
            throw new InvalidArgumentException('This transfer request is no longer valid.');
        }

        return DB::transaction(function () use ($transferRequest, $user) {
            // Accept the request
            $transferRequest->accept();

            // Transfer the organization ownership
            $organization = $transferRequest->organization;
            $organization->transferOwnership($user);

            // Dispatch the event
            OwnershipTransferAccepted::dispatch($transferRequest);

            // Send notification to the previous owner
            $this->sendNotification($transferRequest);

            return $organization->fresh();
        });
    }

    /**
     * Accept a transfer request by token.
     */
    public function handleByToken(string $token, User $user): Organization
    {
        $transferRequest = OwnershipTransferRequest::where('token', $token)->first();

        if (! $transferRequest) {
            throw new InvalidArgumentException('Invalid transfer request token.');
        }

        return $this->handle($transferRequest, $user);
    }

    /**
     * Send notification to the previous owner.
     */
    protected function sendNotification(OwnershipTransferRequest $transferRequest): void
    {
        $previousOwner = $transferRequest->currentOwner;
        $email = $previousOwner->getAttribute('email');

        if ($email) {
            Mail::to($email)->send(new OwnershipTransferCompletedMail($transferRequest, true));
        }
    }
}
