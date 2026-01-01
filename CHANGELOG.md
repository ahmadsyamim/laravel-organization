# Changelog

All notable changes to `laravel-organization` will be documented in this file.

## 1.2.1 - 2026-01-01

### Overview

This release includes critical security fixes, performance improvements, and code quality enhancements for the Laravel Organization package.


---

### Security Fixes

**Authorization Checks for Invitations**

- SendInvitation: Added permission validation to ensure only organization owners or active members can send invitations
- ResendInvitation: Added permission validation to prevent unauthorized users from resending invitations

**Database Transaction for AcceptInvitation**

- Wrapped invitation acceptance and user addition in a database transaction to ensure atomic operations and prevent data inconsistencies


---

### Bug Fixes

**Email Membership Check**

- Fixed critical bug in SendInvitation where the email membership check was querying the pivot table (which doesn't have an email column) instead of joining with the users table


---

### Performance Improvements

**N+1 Query Fixes**

- OrganizationList: Added eager loading for user memberships to prevent N+1 queries when displaying user roles
- InvitationManager: Added eager loading for invitedByUser and organization relationships


---

### Code Quality Improvements

**Centralized Session Management**

- Introduced LaravelOrganization utility class with centralized SESSION_KEY constant and helper methods:
  - getCurrentOrganizationId()
  - setCurrentOrganizationId()
  - clearSession()
  

**Consistent Exception Types**

- Standardized exception types across action classes to use InvalidArgumentException consistently

**Type Safety**

- Added return type hints to model methods (getId(), getOwnerId(), getAcceptedAt(), etc.)
- Added parameter type hints to Livewire component methods
- Updated contracts with proper type declarations

**Enum Usage**

- Replaced hardcoded role strings with OrganizationRole enum values


---

### Breaking Changes

**Authorization Requirements**

- SendInvitation::handle() now requires the inviter to be an organization owner or active member
- ResendInvitation::handle() now requires an authenticated user with permission to manage the organization's invitations


---

### Files Changed

- `src/LaravelOrganization.php` - New utility class
- `src/Actions/SendInvitation.php` - Authorization check + email membership fix
- `src/Actions/AcceptInvitation.php` - Database transaction
- `src/Actions/ResendInvitation.php` - Authorization check
- `src/Actions/DeleteOrganization.php` - Exception type consistency
- `src/Actions/UpdateOrganization.php` - Exception type consistency
- `src/Actions/CreateNewOrganization.php` - Centralized session key
- `src/Livewire/OrganizationList.php` - N+1 fix + type hints
- `src/Livewire/OrganizationSwitcher.php` - Centralized session key + type hints
- `src/Livewire/InvitationManager.php` - Eager loading
- `src/Concerns/InteractsWithOrganization.php` - Centralized session key
- `src/Concerns/InteractsWithUserOrganization.php` - Enum usage
- `src/Concerns/InteractsWithOrganizationSettings.php` - Type hints
- `src/Scopes/OrganizationScope.php` - Centralized session key
- `src/Models/Organization.php` - Return type hints
- `src/Models/Invitation.php` - Return type hints
- `src/Contracts/OrganizationScopingContract.php` - Type declarations
- `src/Contracts/UserOrganizationContract.php` - Type declarations


---

### Upgrade Guide

1. Update any custom code that calls `SendInvitation::handle()` to ensure the inviter is the organization owner or an active member
2. Update any custom code that calls `ResendInvitation::handle()` to pass an authenticated user as the second parameter
3. If you were referencing the session key directly, migrate to using `LaravelOrganization::SESSION_KEY`

## Authorization, Events, Hardening & CI Expansion  - 2025-11-11

### Summary

This release focuses on platform hardening: a comprehensive `OrganizationPolicy`, a full organization lifecycle event suite, stricter validation and error handling in Livewire components, configurable rate limiting for organization creation, zero PHPStan issues (Level 5), expanded multi-version / multi-OS CI matrix, and integrated test coverage reporting. No breaking changes.

### Added

- Authorization layer: `OrganizationPolicy` (11 discrete abilities: viewAny, view, create, update, delete, restore, forceDelete, manageMembers, changeMemberRole, transferOwnership, manageSettings).
  
- Organization lifecycle events:
  
  - `OrganizationCreated`, `OrganizationUpdated`, `OrganizationDeleted`
  - Membership events: `MemberAdded`, `MemberRemoved`, `MemberRoleChanged`
  - Ownership transfer: `OwnershipTransferred`
  
- Invitation event listener wiring groundwork (listener registration for future invitation features without exposing unfinished API).
  
- Invitation Management
  
- Rate limiting for organization creation (configurable in `config/organization.php`).
  
- Expanded CI matrix:
  
  - PHP 8.3 & 8.4
  - Laravel 11 & 12
  - Ubuntu, macOS, Windows runners
  
- Test coverage generation & reporting (HTML + external service badge integration).
  
- Robust validation and structured exception handling in Livewire components (`CreateOrganization`, `UpdateOrganization`, `OrganizationSwitcher`, `OrganizationList`).
  

### Screenshots

<img width="1254" height="708" alt="invitation" src="https://github.com/user-attachments/assets/eeb65daa-1e85-4c37-bbd8-b855068ba459" />
**Invitation Management**
`<livewire:org::invitation-manager :organization="$organization" />` provides send, resend, accept/decline UI with notifications.

Core methods:

```php
sendInvitation();
resendInvitation($uuid);
acceptInvitation($uuid);
declineInvitation($uuid);


```
### Changed

- Service provider (`LaravelOrganizationServiceProvider`) now:
  
  - Registers `OrganizationPolicy` via Gate.
  - Registers event listeners (`InvitationSent` → `SendInvitationEmail`) for future extensibility.
  - Registers additional Livewire components including `invitation-manager` scaffold.
  
- Improved internal consistency of role handling via `OrganizationRole` enum throughout actions and tests.
  
- Documentation expanded for policies, events, and configuration options (authorization & lifecycle usage examples).
  

### Fixed

- Eliminated all PHPStan Level 5 errors (49 → 0) through:
  
  - PHPDoc enrichment
  - Correct builder & relationship typing
  - Removal of unused variables and dead code
  
- Defensive guards added in Livewire components to prevent unhandled exceptions when edge conditions occur.
  
- Stable organization scoping (recursion fix retained; no regressions observed).
  

### Quality / DX

- > 250 tests total (policy + event suites substantially increased coverage).
  
- Architecture tests ensure no debug functions (`dump`, `dd`, `ray`) leak into release code.
  
- Consistent contract bindings for `OrganizationContract`, `OrganizationMembershipContract`, `OrganizationOwnershipContract`, `OrganizationSettingsContract`.
  

### Performance & Safety

- Rate limiting prevents abuse of organization creation (default: 5/hour per user; tune via config).
- Event-driven architecture enables deferred processing (queue-ready serialization in events).

### Documentation

- Policy usage guide: how to integrate with `Gate::allows()` and blade `@can` directives.
- Event dispatch examples: hooking into organization lifecycle for auditing or notifications.
- Configuration reference updated to include rate limiting keys.

### Upgrade Notes

- No breaking changes.
- Optional: publish updated config if you want rate limiting.
  ```bash
  php artisan vendor:publish --tag="laravel-organization-config"
  
  
  ```
- If you already extended your own policy, ensure merging the newly added abilities.
- To leverage events, register listeners in your app (audit logging, notifications, etc.).

### Suggested Post-Upgrade Checks

- Run static analysis: `composer analyse`
- Run tests: `composer test`
- (Optional) Generate coverage report: `composer test-coverage`

### Compare

https://github.com/cleaniquecoders/laravel-organization/compare/1.1.2...1.2.0

### Installation (unchanged)

```bash
composer require cleaniquecoders/laravel-organization
php artisan vendor:publish --tag="laravel-organization-migrations"
php artisan migrate


```
### Snippets

Policy check:

```php
if (Gate::allows('update', $organization)) {
    // proceed
}


```
Listening to an event:

```php
Event::listen(\CleaniqueCoders\LaravelOrganization\Events\OrganizationCreated::class, function ($event) {
    // custom audit log
});


```
Rate limit config fragment (`config/organization.php`):

```php
'rate_limits' => [
    'organization_creation' => [
        'max' => 5,
        'decay_minutes' => 60,
    ],
],


```
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