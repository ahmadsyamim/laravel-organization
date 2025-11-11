<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\InvitationDeclined;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use InvalidArgumentException;

class DeclineInvitation
{
    /**
     * Decline an invitation.
     *
     *
     * @throws InvalidArgumentException
     */
    public function handle(Invitation $invitation): Invitation
    {
        // Validate invitation status
        if (! $invitation->isPending()) {
            throw new InvalidArgumentException('This invitation has already been '.($invitation->isAccepted() ? 'accepted' : 'declined').'.');
        }

        // Validate invitation expiration
        if ($invitation->isExpired()) {
            throw new InvalidArgumentException('This invitation has expired.');
        }

        // Decline the invitation
        $invitation->decline();

        // Dispatch the invitation declined event
        InvitationDeclined::dispatch($invitation);

        return $invitation;
    }
}
