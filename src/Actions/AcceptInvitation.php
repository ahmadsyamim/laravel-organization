<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\InvitationAccepted;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use InvalidArgumentException;

class AcceptInvitation
{
    /**
     * Accept an invitation and add the user to the organization.
     *
     *
     * @throws InvalidArgumentException
     */
    public function handle(Invitation $invitation, User $user): Organization
    {
        // Validate invitation status
        if (! $invitation->isPending()) {
            throw new InvalidArgumentException('This invitation has already been '.($invitation->isAccepted() ? 'accepted' : 'declined').'.');
        }

        // Validate invitation expiration
        if ($invitation->isExpired()) {
            throw new InvalidArgumentException('This invitation has expired.');
        }

        // Validate email matches
        if (strtolower($invitation->email) !== strtolower($user->email)) {
            throw new InvalidArgumentException('The email address does not match the invitation.');
        }

        // Check if user is already a member
        if ($invitation->organization->users()->where('users.id', $user->id)->exists()) {
            throw new InvalidArgumentException('This user is already a member of the organization.');
        }

        // Accept the invitation
        $invitation->accept($user);

        // Add user to organization with the invitation role
        $invitation->organization->addUser($user, $invitation->getRoleEnum());

        // Dispatch the invitation accepted event
        InvitationAccepted::dispatch($invitation);

        return $invitation->organization;
    }
}
