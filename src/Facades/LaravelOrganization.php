<?php

namespace CleaniqueCoders\LaravelOrganization\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\LaravelOrganization\LaravelOrganization
 */
class LaravelOrganization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CleaniqueCoders\LaravelOrganization\LaravelOrganization::class;
    }
}
