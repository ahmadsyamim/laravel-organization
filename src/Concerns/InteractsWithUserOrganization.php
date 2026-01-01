<?php

namespace CleaniqueCoders\LaravelOrganization\Concerns;

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
     * Session key for storing current organization ID.
     */
    protected const ORGANIZATION_SESSION_KEY = 'organization_current_id';

    /**
     * Get the user's current organization ID.
     *
     * Checks session first, then falls back to database (default organization).
     */
    public function getOrganizationId()
    {
        // Check session first (for active switching without DB writes)
        if (session()->has(self::ORGANIZATION_SESSION_KEY)) {
            return session(self::ORGANIZATION_SESSION_KEY);
        }

        // Fallback to database (default organization)
        return $this->organization_id;
    }

    /**
     * Set the user's current organization ID (session-based, no DB write).
     */
    public function setOrganizationId($organizationId): void
    {
        session([self::ORGANIZATION_SESSION_KEY => $organizationId]);
    }

    /**
     * Get the user's default organization ID from database.
     */
    public function getDefaultOrganizationId()
    {
        return $this->organization_id;
    }

    /**
     * Set the user's default organization ID (persisted to database).
     */
    public function setDefaultOrganizationId($organizationId): void
    {
        $this->organization_id = $organizationId;
        $this->save();

        // Also update session to keep in sync
        session([self::ORGANIZATION_SESSION_KEY => $organizationId]);
    }

    /**
     * Sync organization from default (load DB value into session).
     *
     * Call this on login to initialize session with user's default organization.
     */
    public function syncOrganizationFromDefault(): void
    {
        if ($this->organization_id) {
            session([self::ORGANIZATION_SESSION_KEY => $this->organization_id]);
        }
    }

    /**
     * Clear the organization session.
     *
     * Call this on logout to clean up session.
     */
    public function clearOrganizationSession(): void
    {
        session()->forget(self::ORGANIZATION_SESSION_KEY);
    }

    /**
     * Check if user belongs to a specific organization.
     */
    public function belongsToOrganization($organizationId): bool
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
     * Get organizations where user is an administrator.
     */
    public function administratedOrganizations(): BelongsToMany
    {
        return $this->activeOrganizations()->wherePivot('role', 'administrator');
    }

    /**
     * Check if user has a specific role in an organization.
     */
    public function hasRoleInOrganization($organizationId, string $role): bool
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
    public function ownsOrganization($organizationId): bool
    {
        return $this->ownedOrganizations()->where('id', $organizationId)->exists();
    }

    /**
     * Check if user is an administrator of an organization.
     */
    public function isAdministratorOf($organizationId): bool
    {
        return $this->hasRoleInOrganization($organizationId, 'administrator') ||
               $this->ownsOrganization($organizationId);
    }

    /**
     * Check if user is a member of an organization.
     */
    public function isMemberOf($organizationId): bool
    {
        return $this->hasRoleInOrganization($organizationId, 'member') ||
               $this->isAdministratorOf($organizationId);
    }
}
