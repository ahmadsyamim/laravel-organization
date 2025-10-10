<?php

namespace CleaniqueCoders\LaravelOrganization\Concerns;

use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationScopingContract;
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
            if (Auth::check() && Auth::user()->organization_id && ! $model->organization_id) {
                $model->organization_id = Auth::user()->organization_id;
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
     */
    public static function getCurrentOrganizationId(): ?int
    {
        return Auth::check() ? Auth::user()->organization_id : null;
    }

    /**
     * Get the organization ID for this model.
     */
    public function getOrganizationId()
    {
        return $this->organization_id;
    }
}
