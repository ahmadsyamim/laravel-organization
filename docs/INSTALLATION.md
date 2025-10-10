# Installation Guide

## Requirements

- PHP 8.4+
- Laravel 11.0+ or 12.0+

## Installation Steps

### 1. Install the Package

Install the package via Composer:

```bash
composer require cleaniquecoders/laravel-organization
```

### 2. Publish and Run Migrations

Publish the migration files and run them:

```bash
php artisan vendor:publish --tag="org-migrations"
php artisan migrate
```

This will create the necessary database tables:

- `organizations` - Main organization table
- `organization_users` - Pivot table for user-organization relationships

### 3. Publish Configuration (Optional)

Publish the configuration file to customize the package:

```bash
php artisan vendor:publish --tag="org-config"
```

This creates `config/organization.php` with all available configuration options.

### 4. Publish Views (Optional)

If you plan to customize the package views:

```bash
php artisan vendor:publish --tag="org-views"
```

## Configuration

### Basic Configuration

The main configuration options in `config/organization.php`:

```php
return [
    // Specify your User model
    'user-model' => App\Models\User::class,

    // Specify your Organization model (can be custom)
    'organization-model' => CleaniqueCoders\LaravelOrganization\Models\Organization::class,

    // Default settings for new organizations
    'default-settings' => [
        // ... extensive default configuration
    ],

    // Validation rules for settings
    'validation_rules' => [
        // ... validation rules
    ],
];
```

### Custom Models

For SOLID compliance, your custom models should implement the relevant contracts:

**Organization Models** should implement:

- `OrganizationContract` - Core functionality
- `OrganizationMembershipContract` - User management
- `OrganizationOwnershipContract` - Ownership management
- `OrganizationSettingsContract` - Settings management

**User Models** should consider implementing:

- `UserOrganizationContract` - User-side organization interactions

**Models with Organization Scoping** should implement:

- `OrganizationScopingContract` - Multi-tenancy scoping

### Environment Setup

No additional environment variables are required, but you may want to configure:

```env
# Database configuration (standard Laravel)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Verification

After installation, verify everything works:

```bash
# Run the package tests
composer test

# Check if migrations ran successfully
php artisan migrate:status

# Test organization creation via command
php artisan user:create-org user@example.com --organization_name="Test Org"
```

## Next Steps

- Read the [Usage Guide](usage.md) to learn how to use the package
- Check [Contracts Documentation](contracts.md) for implementing custom models
- See [Configuration Guide](configuration.md) for detailed configuration options
