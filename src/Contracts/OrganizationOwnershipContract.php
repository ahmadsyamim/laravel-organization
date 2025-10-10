<?php

namespace CleaniqueCoders\LaravelOrganization\Contracts;

use Illuminate\Foundation\Auth\User;

/**
 * Organization Ownership Contract
 *
 * Defines methods for managing organization ownership.
 * This contract follows the Interface Segregation Principle by focusing
 * specifically on ownership-related operations.
 */
interface OrganizationOwnershipContract
{
    /**
     * Get the owner ID.
     */
    public function getOwnerId();

    /**
     * Set the owner of the organization.
     */
    public function setOwner(User $user): void;

    /**
     * Check if user is the owner of the organization.
     */
    public function isOwnedBy(User $user): bool;

    /**
     * Transfer ownership to another user.
     */
    public function transferOwnership(User $newOwner): void;
}
