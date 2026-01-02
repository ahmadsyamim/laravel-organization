<?php

namespace CleaniqueCoders\LaravelOrganization\Concerns;

use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\LaravelOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait for User models to interact with organizations.
 *
 * Models using this trait should implement UserOrganizationContract
 * to ensure they provide all required user-organization functionality.
 *
 * Uses a hybrid session/database approach:
 * - Session: Used for active switching (no DB writes during switching)
 * - Database: Stores "default" organization loaded on login
 */
trait InteractsWithUserOrganization
{
    /**
     * Get the user's current organization ID.
     *
     * Checks session first, then falls back to database (default organization).
     */
    public function getOrganizationId(): ?int
    {
        // Check session first (for active switching without DB writes)
        if (session()->has(LaravelOrganization::SESSION_KEY)) {
            return session(LaravelOrganization::SESSION_KEY);
        }

        // Fallback to database (default organization)
        return $this->organization_id;
    }

    /**
     * Set the user's current organization ID (session-based, no DB write).
     */
    public function setOrganizationId(?int $organizationId): void
    {
        LaravelOrganization::setCurrentOrganizationId($organizationId);
    }

    /**
     * Get the user's default organization ID from database.
     */
    public function getDefaultOrganizationId(): ?int
    {
        return $this->organization_id;
    }

    /**
     * Set the user's default organization ID (persisted to database).
     */
    public function setDefaultOrganizationId(?int $organizationId): void
    {
        $this->organization_id = $organizationId;
        $this->save();

        // Also update session to keep in sync
        LaravelOrganization::setCurrentOrganizationId($organizationId);
    }

    /**
     * Sync organization from default (load DB value into session).
     *
     * Call this on login to initialize session with user's default organization.
     */
    public function syncOrganizationFromDefault(): void
    {
        if ($this->organization_id) {
            LaravelOrganization::setCurrentOrganizationId($this->organization_id);
        }
    }

    /**
     * Clear the organization session.
     *
     * Call this on logout to clean up session.
     */
    public function clearOrganizationSession(): void
    {
        LaravelOrganization::clearSession();
    }

    /**
     * Check if user belongs to a specific organization.
     */
    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()->where('organization_id', $organizationId)->exists() ||
               $this->ownedOrganizations()->where('id', $organizationId)->exists();
    }

    /**
     * Get all organizations the user is a member of.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            config('organization.organization-model'),
            config('organization.tables.organization_users', 'organization_users')
        )
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get organizations the user owns.
     */
    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(config('organization.organization-model'), 'owner_id');
    }

    /**
     * Get the user's current organization.
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(config('organization.organization-model'), 'organization_id');
    }

    /**
     * Get only active organization memberships.
     */
    public function activeOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('is_active', true);
    }

    /**
     * Get organizations where user is an owner (via pivot table).
     */
    public function ownedOrganizationsViaRole(): BelongsToMany
    {
        return $this->activeOrganizations()->wherePivot('role', OrganizationRole::OWNER->value);
    }

    /**
     * Get organizations where user is an administrator.
     */
    public function administratedOrganizations(): BelongsToMany
    {
        return $this->activeOrganizations()->wherePivot('role', OrganizationRole::ADMINISTRATOR->value);
    }

    /**
     * Check if user has a specific role in an organization.
     */
    public function hasRoleInOrganization(int $organizationId, string $role): bool
    {
        return $this->organizations()
            ->where('organization_id', $organizationId)
            ->wherePivot('role', $role)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Check if user is owner of an organization.
     */
    public function ownsOrganization(int $organizationId): bool
    {
        return $this->ownedOrganizations()->where('id', $organizationId)->exists();
    }

    /**
     * Check if user is an owner of an organization (via pivot role).
     */
    public function isOwnerOf(int $organizationId): bool
    {
        return $this->hasRoleInOrganization($organizationId, OrganizationRole::OWNER->value) ||
               $this->ownsOrganization($organizationId);
    }

    /**
     * Check if user is an administrator of an organization.
     */
    public function isAdministratorOf(int $organizationId): bool
    {
        return $this->hasRoleInOrganization($organizationId, OrganizationRole::ADMINISTRATOR->value) ||
               $this->hasRoleInOrganization($organizationId, OrganizationRole::OWNER->value) ||
               $this->ownsOrganization($organizationId);
    }

    /**
     * Check if user is a member of an organization.
     */
    public function isMemberOf(int $organizationId): bool
    {
        return $this->hasRoleInOrganization($organizationId, OrganizationRole::MEMBER->value) ||
               $this->isAdministratorOf($organizationId);
    }
}
