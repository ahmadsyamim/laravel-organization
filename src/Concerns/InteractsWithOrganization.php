<?php

namespace CleaniqueCoders\LaravelOrganization\Concerns;

use CleaniqueCoders\LaravelOrganization\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for models that are scoped to organizations.
 *
 * Models using this trait should implement OrganizationScopingContract
 * to ensure they provide all required organization scoping functionality.
 */
trait InteractsWithOrganization
{
    /**
     * Boot the trait.
     */
    protected static function bootInteractsWithOrganization(): void
    {
        // Apply global scope
        static::addGlobalScope(new OrganizationScope);

        // Auto-set organization_id on creating
        static::creating(function ($model) {
            if (! $model->organization_id) {
                $organizationId = static::getCurrentOrganizationId();
                if ($organizationId) {
                    $model->organization_id = $organizationId;
                }
            }
        });
    }

    /**
     * Define the relationship to organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(config('organization.organization-model'));
    }

    /**
     * Scope to include models from all organizations.
     */
    public function scopeAllOrganizations(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OrganizationScope::class);
    }

    /**
     * Scope to filter by specific organization.
     */
    public function scopeForOrganization(Builder $query, $organizationId): Builder
    {
        return $query->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId);
    }

    /**
     * Get the current organization ID from auth user.
     *
     * Safely retrieves the organization_id without triggering relationships
     * or causing recursive query execution.
     */
    public static function getCurrentOrganizationId(): ?int
    {
        if (! Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Use getAttributeValue() to get the raw value without triggering relationships
        // This method accesses the attribute directly, bypassing relationship lazy loading
        // @phpstan-ignore-next-line
        return method_exists($user, 'getAttributeValue')
            ? $user->getAttributeValue('organization_id')
            : ($user->organization_id ?? null);
    }

    /**
     * Get the organization ID for this model.
     */
    public function getOrganizationId()
    {
        return $this->organization_id;
    }
}
