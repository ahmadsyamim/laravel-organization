<?php

namespace CleaniqueCoders\LaravelOrganization;

use CleaniqueCoders\LaravelOrganization\Console\Commands\CreateOrganizationCommand;
use CleaniqueCoders\LaravelOrganization\Console\Commands\DeleteOrganizationCommand;
use CleaniqueCoders\LaravelOrganization\Console\Commands\UpdateOrganizationCommand;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use CleaniqueCoders\LaravelOrganization\Listeners\SendInvitationEmail;
use CleaniqueCoders\LaravelOrganization\Livewire\CreateOrganization;
use CleaniqueCoders\LaravelOrganization\Livewire\InvitationManager;
use CleaniqueCoders\LaravelOrganization\Livewire\OrganizationList;
use CleaniqueCoders\LaravelOrganization\Livewire\OrganizationSwitcher;
use CleaniqueCoders\LaravelOrganization\Livewire\TransferOwnership;
use CleaniqueCoders\LaravelOrganization\Livewire\UpdateOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Policies\OrganizationPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
            ->hasConfigFile('organization')
            ->hasViews()
            ->hasRoute('web')
            ->hasMigration('create_organization_table')
            ->hasMigration('create_invitations_table')
            ->hasMigration('create_ownership_transfer_requests_table')
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

    /**
     * Register Livewire components with version-aware registration.
     *
     * Livewire 4 changed how namespaced components (using ::) are resolved.
     * In v4, namespaced components only check classNamespaces, not classComponents.
     * This method uses the appropriate registration method for each version.
     */
    protected function registerLivewireComponents(): void
    {
        if ($this->isLivewire4()) {
            // Livewire 4: Use addNamespace with full class namespace
            // Components will be named based on their class names in kebab-case
            // e.g., OrganizationSwitcher -> org::organization-switcher
            Livewire::addNamespace('org', classNamespace: 'CleaniqueCoders\\LaravelOrganization\\Livewire');

            // Also register aliases for backward compatibility with short names
            // These map the old short names to the new kebab-case names
            Livewire::component('org.switcher', OrganizationSwitcher::class);
            Livewire::component('org.create', CreateOrganization::class);
            Livewire::component('org.update', UpdateOrganization::class);
            Livewire::component('org.list', OrganizationList::class);
            Livewire::component('org.invitation-manager', InvitationManager::class);
            Livewire::component('org.transfer-ownership', TransferOwnership::class);
        } else {
            // Livewire 3: Register components individually with custom names
            Livewire::component('org::switcher', OrganizationSwitcher::class);
            Livewire::component('org::create', CreateOrganization::class);
            Livewire::component('org::update', UpdateOrganization::class);
            Livewire::component('org::list', OrganizationList::class);
            Livewire::component('org::invitation-manager', InvitationManager::class);
            Livewire::component('org::transfer-ownership', TransferOwnership::class);
        }
    }

    /**
     * Determine if Livewire 4 is being used.
     */
    protected function isLivewire4(): bool
    {
        return property_exists(\Livewire\LivewireManager::class, 'v4')
            && \Livewire\LivewireManager::$v4 === true;
    }

    public function packageBooted(): void
    {
        // Register the OrganizationPolicy
        Gate::policy(Organization::class, OrganizationPolicy::class);

        // Register event listeners
        Event::listen(InvitationSent::class, SendInvitationEmail::class);

        // Register Livewire components
        if (class_exists(Livewire::class)) {
            $this->registerLivewireComponents();
        }
    }
}
