# Laravel Organization Package - AI Coding Instructions

## Project Overview
This is a Laravel package for organization tenancy using **Spatie's Laravel Package Tools** as the foundation. The package follows modern Laravel package development patterns with strict code quality standards.

## Architecture & Structure

### Core Components
- **Service Provider**: `LaravelOrganizationServiceProvider` extends `Spatie\LaravelPackageTools\PackageServiceProvider`
- **Main Class**: `LaravelOrganization` - currently minimal, likely intended for organization business logic
- **Facade**: `LaravelOrganization` provides static access to the main class
- **Command**: `LaravelOrganizationCommand` - Artisan command for package operations
- **Migration**: `create_organization_table.php.stub` - publishable migration template

### Namespace Convention
All classes use `CleaniqueCoders\LaravelOrganization` namespace with PSR-4 autoloading.

## Development Workflow

### Testing Framework
- **Primary**: Pest PHP with Laravel plugin (`pestphp/pest-plugin-laravel`)
- **Base Class**: `TestCase` extends `Orchestra\Testbench\TestCase` for package testing
- **Architecture Tests**: `ArchTest.php` enforces no debugging functions (`dd`, `dump`, `ray`)
- **Factory Guessing**: Auto-resolves factory names using `CleaniqueCoders\LaravelOrganization\Database\Factories\{Model}Factory`

### Code Quality Tools
- **Static Analysis**: PHPStan level 5 with Laravel-specific rules (`larastan/larastan`)
- **Code Style**: Laravel Pint with automatic GitHub Action formatting
- **Coverage**: Available via `composer test-coverage`

### Essential Commands
```bash
composer test          # Run Pest tests
composer analyse        # PHPStan static analysis
composer format         # Laravel Pint code formatting
composer test-coverage  # Test coverage report
```

## Package-Specific Patterns

### Service Provider Configuration
Uses Spatie's fluent package configuration:
```php
$package
    ->name('laravel-organization')
    ->hasConfigFile()
    ->hasViews()
    ->hasMigration('create_laravel_organization_table')
    ->hasCommand(LaravelOrganizationCommand::class);
```

### Publishing Assets
- **Config**: `--tag="laravel-organization-config"`
- **Migrations**: `--tag="laravel-organization-migrations"`
- **Views**: `--tag="laravel-organization-views"`

### CI/CD Pipeline
- **Multi-version Testing**: PHP 8.3-8.4, Laravel 11-12, Ubuntu/Windows
- **Auto-formatting**: Commits style fixes automatically
- **Quality Gates**: PHPStan analysis on every push

## File Structure Rules
- **Migrations**: Use `.stub` extension for publishable migrations
- **Factories**: Place in `database/factories/` with `{Model}Factory` naming
- **Config**: Single file at `config/organization.php`
- **Tests**: All tests inherit from project's `TestCase`

## Key Integration Points
- **Orchestra Testbench**: Essential for testing Laravel package functionality
- **Spatie Package Tools**: Handles service provider boilerplate and asset publishing
- **Laravel Framework**: Supports versions 11-12 with contracts integration

When working on this package, always consider the multi-tenant organization context and maintain compatibility across supported Laravel versions.
