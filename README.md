# Laravel Organization

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-organization.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-organization)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/laravel-organization/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/laravel-organization/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/laravel-organization/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/laravel-organization/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-organization.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-organization)
[![Code Coverage](https://codecov.io/gh/cleaniquecoders/laravel-organization/branch/main/graph/badge.svg?style=flat-square)](https://codecov.io/gh/cleaniquecoders/laravel-organization)

A comprehensive Laravel package for implementing organization-based tenancy in your application. This package provides a complete solution for managing organizations, user memberships, roles, and automatic data scoping with SOLID principles compliance.

## Key Features

- **🏢 Organization Management** - Complete CRUD operations with UUID and slug support
- **👥 User Membership** - Flexible role-based membership system with administrators and members
- **🔒 Automatic Data Scoping** - Seamless multi-tenancy through Eloquent global scopes
- **⚙️ Comprehensive Settings** - Extensive JSON-based configuration system with validation
- **🧩 SOLID Principles** - Contract-based architecture for flexibility and testability
- **🛠️ Developer Friendly** - Built-in factories, commands, and trait-based integration

## Screenshots

### Organization Management

![Organization Dashboard](screenshots/organization.png)

### Organization Switcher

![Organization Switcher](screenshots/organization-switcher.png)

### Create New Organization

![Create New Organization](screenshots/create-new-organization.png)

## Quick Start

### Installation

```bash
# Install the package
composer require cleaniquecoders/laravel-organization

# Publish and run migrations
php artisan vendor:publish --tag="org-migrations"
php artisan migrate

# (Optional) Publish configuration file
php artisan vendor:publish --tag="org-config"

# (Optional) Publish views for customization
php artisan vendor:publish --tag="org-views"
```

> **Note:** The migration will create `organizations`, `organization_users` tables and add an `organization_id` column to your `users` table.

### Basic Usage

```php
use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

// Create an organization
$organization = (new CreateNewOrganization())->handle($user);

// Add members
$organization->addUser($user, OrganizationRole::ADMINISTRATOR);
$organization->addUser($member, OrganizationRole::MEMBER);

// Configure settings
$organization->setSetting('app.timezone', 'America/New_York');
$organization->setSetting('features.api_access', true);
$organization->save();
```

### Add to Your User Model

```php
use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithUserOrganization;
use CleaniqueCoders\LaravelOrganization\Contracts\UserOrganizationContract;

class User extends Authenticatable implements UserOrganizationContract
{
    use InteractsWithUserOrganization;

    protected $fillable = ['name', 'email', 'password', 'organization_id'];
}

// Now you can:
$user->organizations;              // Get all user's organizations
$user->currentOrganization;        // Get current organization
$user->ownedOrganizations;         // Get organizations user owns
$user->belongsToOrganization($id); // Check membership
$user->isAdministratorOf($id);     // Check admin role
```

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[📖 Installation Guide](docs/02-development/01-installation.md)** - Detailed setup instructions
- **[🚀 Usage Guide](docs/02-development/02-configuration.md)** - Complete usage examples and patterns
- **[⚙️ Configuration](docs/02-development/02-configuration.md)** - All configuration options and customization
- **[🎯 Features Overview](docs/01-architecture/01-overview.md)** - Complete feature breakdown
- **[🔧 Contracts Documentation](docs/01-architecture/02-contracts.md)** - SOLID principles and custom implementations
- **[🧩 Components & Actions](docs/03-usage/03-components-and-actions.md)** - Livewire components and action classes

## Requirements

- PHP 8.4+
- Laravel 11.0+ or 12.0+

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)
