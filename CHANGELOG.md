# Changelog

All notable changes to `laravel-organization` will be documented in this file.

## Added Actions & Livewire Components - 2025-10-10

### v1.1.0 - Enhanced Package with Actions, Commands & Livewire - 2025-10-10

This release introduces comprehensive organization management tools through actions, Artisan commands, and Livewire components.

#### 🎯 Actions

Three powerful action classes for organization lifecycle management:

- **CreateNewOrganization** - Create organizations with automatic slug generation and default settings
- **UpdateOrganization** - Update organizations with permission validation and business rules
- **DeleteOrganization** - Safely delete organizations with comprehensive business logic checks

#### ⚡ Artisan Commands

Console commands for organization management:

- `php artisan organization:create` - Interactive organization creation
- `php artisan organization:update` - Update organization details via CLI
- `php artisan organization:delete` - Delete organizations with confirmation

#### 🎨 Livewire Components

Pre-built UI components for seamless integration:

- **CreateOrganization** - Organization creation form with validation
- **UpdateOrganization** - Organization editing interface
- **OrganizationList** - Display and manage organization listings
- **OrganizationSwitcher** - Switch between user's organizations

<img width="533" height="464" alt="create-new-organization" src="https://github.com/user-attachments/assets/27746bf5-212b-4dbb-89ef-8d1ad798a212" />
<img width="350" height="325" alt="organization-switcher" src="https://github.com/user-attachments/assets/5c91c74b-1956-462e-a3a8-657f31e7171a" />
<img width="1233" height="476" alt="organization" src="https://github.com/user-attachments/assets/f02c9346-32c0-488b-90f5-a8e3b039b227" />
#### 📚 Documentation

Complete documentation added for:

- Features overview
- Actions and components usage
- Configuration options
- Contracts and interfaces

**Full Changelog**: https://github.com/cleaniquecoders/laravel-organization/compare/1.0.3...1.1.0

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