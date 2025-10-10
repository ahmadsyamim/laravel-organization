<?php

namespace App\Concerns;

use CleaniqueCoders\LaravelOrganization\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
    public function organization()
    {
        return $this->belongsTo(config('organization.user-model'));
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
}
