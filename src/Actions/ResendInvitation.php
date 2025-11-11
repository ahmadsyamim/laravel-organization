<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ResendInvitation
{
    /**
     * The default expiration time for invitations in days.
     */
    private const DEFAULT_EXPIRATION_DAYS = 7;

    /**
     * Resend an invitation, generating a new token and expiration.
     *
     *
     * @throws InvalidArgumentException
     */
    public function handle(Invitation $invitation, int $expirationDays = self::DEFAULT_EXPIRATION_DAYS): Invitation
    {
        // Only allow resending pending invitations
        if (! $invitation->isPending()) {
            throw new InvalidArgumentException('Cannot resend an invitation that has been '.($invitation->isAccepted() ? 'accepted' : 'declined').'.');
        }

        // Update the invitation with a new token and expiration
        $invitation->update([
            'token' => Str::random(40),
            'expires_at' => now()->addDays($expirationDays),
        ]);

        // Dispatch the invitation sent event (for email resend)
        InvitationSent::dispatch($invitation);

        return $invitation;
    }
}
