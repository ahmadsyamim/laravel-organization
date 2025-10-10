<?php

namespace CleaniqueCoders\LaravelOrganization;

use CleaniqueCoders\LaravelOrganization\Console\Commands\CreateOrganizationCommand;
use CleaniqueCoders\LaravelOrganization\Console\Commands\DeleteOrganizationCommand;
use CleaniqueCoders\LaravelOrganization\Console\Commands\UpdateOrganizationCommand;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;
use CleaniqueCoders\LaravelOrganization\Livewire\CreateOrganization;
use CleaniqueCoders\LaravelOrganization\Livewire\OrganizationList;
use CleaniqueCoders\LaravelOrganization\Livewire\OrganizationSwitcher;
use CleaniqueCoders\LaravelOrganization\Livewire\UpdateOrganization;
use Livewire\Livewire;
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
            ->name('org')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_organization_table')
            ->hasCommands([
                CreateOrganizationCommand::class,
                DeleteOrganizationCommand::class,
                UpdateOrganizationCommand::class,
            ]);
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

    public function packageBooted(): void
    {
        // Register Livewire components
        if (class_exists(Livewire::class)) {
            Livewire::component('org::switcher', OrganizationSwitcher::class);
            Livewire::component('org::create', CreateOrganization::class);
            Livewire::component('org::update', UpdateOrganization::class);
            Livewire::component('org::list', OrganizationList::class);
        }
    }
}
