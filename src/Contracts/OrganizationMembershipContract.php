<?php

namespace CleaniqueCoders\LaravelOrganization\Contracts;

use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;

/**
 * Organization Membership Contract
 *
 * Defines methods for managing user membership within organizations.
 * This contract follows the Interface Segregation Principle by focusing
 * specifically on membership-related operations.
 */
interface OrganizationMembershipContract
{
    /**
     * Get all users (members) of the organization.
     */
    public function users(): BelongsToMany;

    /**
     * Get only active users of the organization.
     */
    public function activeUsers(): BelongsToMany;

    /**
     * Get administrators of the organization.
     */
    public function administrators(): BelongsToMany;

    /**
     * Get members of the organization.
     */
    public function members(): BelongsToMany;

    /**
     * Get all active members of the organization.
     */
    public function allMembers();

    /**
     * Check if user is a member of the organization.
     */
    public function hasMember(User $user): bool;

    /**
     * Check if user is an active member of the organization.
     */
    public function hasActiveMember(User $user): bool;

    /**
     * Add a user to the organization with a specific role.
     */
    public function addUser(User $user, OrganizationRole $role = OrganizationRole::MEMBER, bool $isActive = true): void;

    /**
     * Remove a user from the organization.
     */
    public function removeUser(User $user): void;

    /**
     * Update user's role in the organization.
     */
    public function updateUserRole(User $user, OrganizationRole $role): void;

    /**
     * Activate or deactivate a user in the organization.
     */
    public function setUserActiveStatus(User $user, bool $isActive): void;

    /**
     * Get user's role in the organization.
     */
    public function getUserRole(User $user): ?OrganizationRole;

    /**
     * Check if user has a specific role in the organization.
     */
    public function userHasRole(User $user, OrganizationRole $role): bool;
}
