<?php

namespace CleaniqueCoders\LaravelOrganization;

use CleaniqueCoders\LaravelOrganization\Commands\LaravelOrganizationCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelOrganizationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-organization')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_organization_table')
            ->hasCommand(LaravelOrganizationCommand::class);
    }
}
