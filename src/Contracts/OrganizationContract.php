<?php

namespace CleaniqueCoders\LaravelOrganization\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Core Organization Contract
 *
 * Defines the essential methods that any organization implementation must have.
 * This contract follows the Single Responsibility Principle by focusing only
 * on core organization identity and basic relationships.
 */
interface OrganizationContract
{
    /**
     * Get the organization's unique identifier.
     */
    public function getId();

    /**
     * Get the organization's UUID.
     */
    public function getUuid(): string;

    /**
     * Get the organization's name.
     */
    public function getName(): string;

    /**
     * Get the organization's slug.
     */
    public function getSlug(): string;

    /**
     * Get the organization's description.
     */
    public function getDescription(): ?string;

    /**
     * Get the owner relationship.
     */
    public function owner(): BelongsTo;

    /**
     * Check if the organization is active (not soft deleted).
     */
    public function isActive(): bool;
}
