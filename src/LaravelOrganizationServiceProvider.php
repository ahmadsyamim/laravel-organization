<?php

namespace CleaniqueCoders\LaravelOrganization;

use CleaniqueCoders\LaravelOrganization\Commands\LaravelOrganizationCommand;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;
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

    public function packageRegistered(): void
    {
        // Bind contracts to the configured organization model
        // This follows the Dependency Inversion Principle
        $this->app->bind(OrganizationContract::class, function ($app) {
            return $app->make(config('organization.organization-model'));
        });

        $this->app->bind(OrganizationMembershipContract::class, function ($app) {
            return $app->make(config('organization.organization-model'));
        });

        $this->app->bind(OrganizationOwnershipContract::class, function ($app) {
            return $app->make(config('organization.organization-model'));
        });

        $this->app->bind(OrganizationSettingsContract::class, function ($app) {
            return $app->make(config('organization.organization-model'));
        });
    }
}
