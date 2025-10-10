<?php

namespace CleaniqueCoders\LaravelOrganization\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Organization Scoping Contract
 *
 * Defines methods for models that are scoped to organizations.
 * This contract follows the Interface Segregation Principle by focusing
 * on organization scoping functionality for multi-tenant models.
 */
interface OrganizationScopingContract
{
    /**
     * Define the relationship to organization.
     */
    public function organization(): BelongsTo;

    /**
     * Scope to include models from all organizations.
     */
    public function scopeAllOrganizations(Builder $query): Builder;

    /**
     * Scope to filter by specific organization.
     */
    public function scopeForOrganization(Builder $query, $organizationId): Builder;

    /**
     * Get the current organization ID from auth user.
     */
    public static function getCurrentOrganizationId(): ?int;

    /**
     * Get the organization ID for this model.
     */
    public function getOrganizationId();
}
