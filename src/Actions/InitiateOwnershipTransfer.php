<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferRequested;
use CleaniqueCoders\LaravelOrganization\Mail\OwnershipTransferRequestMail;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class InitiateOwnershipTransfer
{
    use AsAction;

    /**
     * Default expiration time in hours.
     */
    protected int $expirationHours = 72;

    /**
     * Initiate an ownership transfer request.
     *
     * @param  Organization  $organization  The organization to transfer
     * @param  User  $currentOwner  The current owner initiating the transfer
     * @param  User  $newOwner  The new owner to receive ownership
     * @param  string|null  $message  Optional message to include with the request
     * @return OwnershipTransferRequest The created transfer request
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function handle(
        Organization $organization,
        User $currentOwner,
        User $newOwner,
        ?string $message = null
    ): OwnershipTransferRequest {
        // Validate that the current user is the owner
        if (! $organization->isOwnedBy($currentOwner)) {
            throw new InvalidArgumentException('Only the current owner can initiate an ownership transfer.');
        }

        // Validate that the new owner is different from the current owner
        if ($currentOwner->id === $newOwner->id) {
            throw new InvalidArgumentException('Cannot transfer ownership to yourself.');
        }

        // Check for existing pending transfer requests for this organization
        $existingRequest = OwnershipTransferRequest::pending()
            ->forOrganization($organization->id)
            ->first();

        if ($existingRequest) {
            throw new InvalidArgumentException('There is already a pending transfer request for this organization. Please cancel it first.');
        }

        // Create the transfer request
        $transferRequest = OwnershipTransferRequest::create([
            'uuid' => Str::orderedUuid()->toString(),
            'organization_id' => $organization->id,
            'current_owner_id' => $currentOwner->id,
            'new_owner_id' => $newOwner->id,
            'token' => Str::random(64),
            'message' => $message,
            'expires_at' => now()->addHours($this->expirationHours),
        ]);

        // Dispatch the event
        OwnershipTransferRequested::dispatch($transferRequest);

        // Send email notification to the new owner
        $this->sendNotification($transferRequest);

        return $transferRequest;
    }

    /**
     * Send email notification to the new owner.
     */
    protected function sendNotification(OwnershipTransferRequest $transferRequest): void
    {
        $newOwner = $transferRequest->newOwner;
        $email = $newOwner->getAttribute('email');

        if ($email) {
            Mail::to($email)->send(new OwnershipTransferRequestMail($transferRequest));
        }
    }

    /**
     * Set the expiration time in hours.
     */
    public function setExpirationHours(int $hours): self
    {
        $this->expirationHours = $hours;

        return $this;
    }
}
