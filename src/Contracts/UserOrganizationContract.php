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
     * Set the user's current organization ID (session-based).
     */
    public function setOrganizationId($organizationId): void;

    /**
     * Get the user's default organization ID from database.
     */
    public function getDefaultOrganizationId();

    /**
     * Set the user's default organization ID (persisted to database).
     */
    public function setDefaultOrganizationId($organizationId): void;

    /**
     * Sync organization from default (load DB value into session).
     */
    public function syncOrganizationFromDefault(): void;

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
