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
        if (Auth::check() && Auth::user()->organization_id) {
            $builder->where('organization_id', Auth::user()->organization_id);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutOrganizationScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('withOrganization', function (Builder $builder, $organizationId) {
            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->getTable().'.organization_id', $organizationId);
        });

        $builder->macro('allOrganizations', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
