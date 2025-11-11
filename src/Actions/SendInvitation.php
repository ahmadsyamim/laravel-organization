<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SendInvitation
{
    /**
     * The default expiration time for invitations in days.
     */
    private const DEFAULT_EXPIRATION_DAYS = 7;

    /**
     * Send an invitation to join the organization.
     *
     *
     * @throws InvalidArgumentException
     */
    public function handle(
        Organization $organization,
        User $invitedBy,
        string $email,
        OrganizationRole $role = OrganizationRole::MEMBER,
        int $expirationDays = self::DEFAULT_EXPIRATION_DAYS
    ): Invitation {
        // Validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$email}");
        }

        // Check if email already has an active membership in the organization
        $existingMember = $organization->users()
            ->where('email', $email)
            ->exists();

        if ($existingMember) {
            throw new InvalidArgumentException("User with email {$email} is already a member of this organization.");
        }

        // Check if there's an active/pending invitation for this email
        $existingInvitation = Invitation::query()
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->where(function ($query) {
                $query->whereNull('accepted_at')
                    ->whereNull('declined_at')
                    ->where('expires_at', '>', now());
            })
            ->first();

        if ($existingInvitation) {
            throw new InvalidArgumentException("An active invitation already exists for {$email}.");
        }

        // Create the invitation
        $invitation = Invitation::create([
            'uuid' => Str::orderedUuid()->toString(),
            'organization_id' => $organization->id,
            'invited_by_user_id' => $invitedBy->id,
            'email' => strtolower($email),
            'token' => $this->generateToken(),
            'role' => $role->value,
            'expires_at' => now()->addDays($expirationDays),
        ]);

        // Dispatch the invitation sent event
        InvitationSent::dispatch($invitation);

        return $invitation;
    }

    /**
     * Generate a unique token for the invitation.
     */
    private function generateToken(): string
    {
        return Str::random(40);
    }
}
