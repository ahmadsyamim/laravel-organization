<?php

namespace CleaniqueCoders\LaravelOrganization\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class OrganizationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        $organizationId = $this->getCurrentOrganizationId();

        if ($organizationId) {
            $builder->where('organization_id', $organizationId);
        }
    }

    /**
     * Session key for storing current organization ID.
     */
    protected const ORGANIZATION_SESSION_KEY = 'organization_current_id';

    /**
     * Get the current organization ID safely without triggering recursive queries.
     *
     * Uses hybrid session/database approach:
     * 1. Check session first (for active switching without DB writes)
     * 2. Fall back to database (user's default organization)
     */
    protected function getCurrentOrganizationId(): ?int
    {
        // Check session first (for active switching without DB writes)
        if (session()->has(self::ORGANIZATION_SESSION_KEY)) {
            return session(self::ORGANIZATION_SESSION_KEY);
        }

        if (! Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Use getAttributeValue() to get the raw value without triggering relationships
        // This method accesses the attribute directly, bypassing relationship lazy loading
        return method_exists($user, 'getAttributeValue')
            ? $user->getAttributeValue('organization_id')
            : ($user->organization_id ?? null);
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutOrganizationScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(self::class);
        });

        $builder->macro('withOrganization', function (Builder $builder, $organizationId) {
            return $builder->withoutGlobalScope(self::class)
                ->where($builder->getModel()->getTable().'.organization_id', $organizationId);
        });

        $builder->macro('allOrganizations', function (Builder $builder) {
            return $builder->withoutGlobalScope(self::class);
        });
    }
}
