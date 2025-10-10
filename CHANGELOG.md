# Changelog

All notable changes to `laravel-organization` will be documented in this file.

## Fixed Recursion - 2025-10-10

### Memory Exhaustion Fix - Infinite Loop Prevention

#### Problem Description

When users registered and received email verification, the application would hang and eventually hit PHP's memory limit with errors like:

```
PHP Fatal error: Allowed memory size of 2147483648 bytes exhausted (tried to allocate 12288 bytes)
in vendor/laravel/framework/src/Illuminate/Database/Eloquent/SoftDeletingScope.php on line 121

```
#### Root Cause

The issue was caused by a **circular reference/infinite loop** in the organization scoping mechanism:

1. `OrganizationScope::apply()` is called when querying models with `InteractsWithOrganization` trait
2. The scope accessed `Auth::user()->organization_id`
3. Accessing `->organization_id` as a property triggers Laravel's attribute accessor
4. If the User model has a `currentOrganization()` relationship, this might load the Organization model
5. The Organization model may have relationships to other models that use `InteractsWithOrganization`
6. Those models apply `OrganizationScope` again...
7. **Back to step 1 - infinite loop!**

##### Why It Happened During Registration/Verification

During registration and email verification:

- The user is authenticated
- Multiple queries are executed (user lookup, email verification, redirects)
- Each query on scoped models triggers the scope
- The scope accesses the user model repeatedly
- This creates an exponential cascade of queries

#### The Fix

##### Changed Files

1. **`src/Scopes/OrganizationScope.php`**
2. **`src/Concerns/InteractsWithOrganization.php`**

##### Solution Approach

Instead of accessing `Auth::user()->organization_id` (which can trigger relationships and accessors), we now:

1. **Access raw attributes directly** from the model's `attributes` property
2. **Bypass relationship loading** and any getters/accessors
3. **Prevent recursive scope application**

##### Code Changes

###### Before (Problematic):

```php
public function apply(Builder $builder, Model $model)
{
    if (Auth::check() && Auth::user()->organization_id) {
        $builder->where('organization_id', Auth::user()->organization_id);
    }
}

```
###### After (Fixed):

```php
public function apply(Builder $builder, Model $model)
{
    $organizationId = $this->getCurrentOrganizationId();

    if ($organizationId) {
        $builder->where('organization_id', $organizationId);
    }
}

protected function getCurrentOrganizationId(): ?int
{
    if (! Auth::check()) {
        return null;
    }

    $user = Auth::user();

    // Access the raw attribute directly to prevent triggering relationships
    if ($user && property_exists($user, 'attributes') && isset($user->attributes['organization_id'])) {
        return $user->attributes['organization_id'];
    }

    return null;
}

```
##### Key Technical Details

1. **Direct Attribute Access**: `$user->attributes['organization_id']` accesses the raw database column value
2. **No Magic Methods**: Bypasses `__get()`, accessors, and relationship loading
3. **Safe Property Check**: Uses `property_exists()` to ensure the attributes array exists
4. **Null Safety**: Gracefully handles null organization_id and unauthenticated users

#### Testing

A comprehensive test suite was added in `tests/OrganizationScopeRecursionTest.php` to verify:

1. No infinite recursion when accessing organization relationships
2. Safe retrieval of organization_id without triggering relationships
3. Graceful handling of null organization_id
4. Proper behavior when user is not authenticated

#### Prevention

To prevent similar issues in the future:

1. **Never access relationships in global scopes** - always access raw attributes
2. **Be cautious with `Auth::user()` properties** - they may trigger lazy loading
3. **Test with authentication flows** - registration, verification, password reset
4. **Monitor query counts** - excessive queries may indicate recursion
5. **Use Laravel Debugbar or Telescope** during development to catch query loops early

#### Impact

This fix resolves:

- ✅ Memory exhaustion during registration
- ✅ Hanging requests during email verification
- ✅ Infinite loops in organization-scoped queries
- ✅ Performance issues from recursive query execution

#### Compatibility

- **Laravel**: 11.x, 12.x
- **PHP**: 8.3, 8.4
- **Breaking Changes**: None - this is a bug fix with no API changes

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