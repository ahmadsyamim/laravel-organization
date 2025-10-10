# Changelog

All notable changes to `laravel-organization` will be documented in this file.

## Update gitignore to include docs/ directory - 2025-10-10

**Full Changelog**: https://github.com/cleaniquecoders/laravel-organization/compare/1.0.2...1.0.3

## Fix migration import models to use config - 2025-10-10

**Full Changelog**: https://github.com/cleaniquecoders/laravel-organization/compare/1.0.1...1.0.2

## Fix migration - 2025-10-10

**Full Changelog**: https://github.com/cleaniquecoders/laravel-organization/compare/1.0.0...1.0.1

## First Release - 2025-10-10

### Laravel Organization

Complete Laravel package for organization-based multi-tenancy with automatic data scoping, and role management.

### ✨ What's Included

- 🏢 Organization CRUD with UUID/slug support
- 👥 Role-based membership (Admin/Member)
- 🔒 Automatic data scoping for multi-tenancy
- ⚙️ Comprehensive settings system with validation

### 📦 Installation

```bash
composer require cleaniquecoders/laravel-organization
php artisan vendor:publish --tag="laravel-organization-migrations"
php artisan migrate




```
### 🚀 Quick Usage

```php
// Create org & add members
$org = (new CreateNewOrganization())->handle($user);
$org->addUser($member, OrganizationRole::MEMBER);

// Auto-scope your models
class Post extends Model {
    use InteractsWithOrganization;
}




```