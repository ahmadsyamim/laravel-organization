<?php

namespace CleaniqueCoders\LaravelOrganization\Contracts;

/**
 * User Organization Contract
 *
 * Defines methods that users must implement to interact with organizations.
 * This contract follows the Interface Segregation Principle by focusing
 * on user-side organization functionality.
 */
interface UserOrganizationContract
{
    /**
     * Get the user's current organization ID.
     */
    public function getOrganizationId();

    /**
     * Set the user's organization ID.
     */
    public function setOrganizationId($organizationId): void;

    /**
     * Check if user belongs to a specific organization.
     */
    public function belongsToOrganization($organizationId): bool;

    /**
     * Get all organizations the user is a member of.
     */
    public function organizations();

    /**
     * Get organizations the user owns.
     */
    public function ownedOrganizations();
}
