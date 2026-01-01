# Organization Switching

This guide covers the hybrid session/database approach for organization
switching in the Laravel Organization package.

## Overview

The package uses a **hybrid approach** for organization context management:

- **Session**: Used for active switching (no database writes during switching)
- **Database**: Stores the user's "default" organization (loaded on login)
- **Set as Default**: Optional action to persist current organization to database

This approach provides:

- **Better performance**: No database writes on every organization switch
- **Persistence**: Default organization survives session expiration
- **Flexibility**: Users can switch freely without impacting their default

## LaravelOrganization Utility Class

The package provides a centralized utility class for session management:

```php
use CleaniqueCoders\LaravelOrganization\LaravelOrganization;

// Session key constant
LaravelOrganization::SESSION_KEY; // 'organization_current_id'

// Get current organization ID (session first, then database)
$orgId = LaravelOrganization::getCurrentOrganizationId();

// Set current organization ID in session
LaravelOrganization::setCurrentOrganizationId($organizationId);

// Clear organization session
LaravelOrganization::clearSession();
```

This class is used internally by all components for consistent session handling. You can use it directly when you need low-level control over the organization context.

## How It Works

### Session-Based Switching

When a user switches organizations, the new organization ID is stored
in the session only:

```php
// This stores in session, NOT database
$user->setOrganizationId($organizationId);

// Session key used: 'organization_current_id'
```

### Database Default

The user's `organization_id` column in the database stores their **default** organization:

```php
// Get the default (database) organization
$defaultOrgId = $user->getDefaultOrganizationId();

// Set a new default (writes to database)
$user->setDefaultOrganizationId($organizationId);
```

### Resolution Order

When retrieving the current organization, the system checks:

1. **Session first** - Active switching context
2. **Database fallback** - User's default organization

```php
// This checks session first, then falls back to database
$currentOrgId = $user->getOrganizationId();
```

## User Model Methods

The `InteractsWithUserOrganization` trait provides these methods:

### Getting Organization ID

```php
// Get current organization (session first, then database)
$user->getOrganizationId();

// Get default organization (database only)
$user->getDefaultOrganizationId();
```

### Setting Organization ID

```php
// Switch organization (session only, no DB write)
$user->setOrganizationId($organizationId);

// Set as default (writes to database AND session)
$user->setDefaultOrganizationId($organizationId);
```

### Session Management

```php
// On login: sync default organization to session
$user->syncOrganizationFromDefault();

// On logout: clear organization session
$user->clearOrganizationSession();
```

## Livewire Components

### OrganizationSwitcher

The switcher component now has two actions:

1. **Switch** - Changes current organization (session only)
2. **Set as Default** - Persists to database

```blade
<livewire:org::switcher :user="auth()->user()" />
```

The component shows:

- Current organization with "(Default)" badge if it matches database
- "Set Default" button when current org is not the default

### OrganizationList

The list component shows both "Current" and "Default" badges:

```blade
<livewire:org::list />
```

Actions available:

- **Switch To** - Change current organization (session)
- **Set Default** - Persist to database

## Events

### organization-switched

Dispatched when switching organizations (session-based):

```javascript
Livewire.on('organization-switched', (data) => {
    console.log('Switched to:', data.organizationId);
    // Optionally refresh page or update UI
});
```

### default-organization-changed

Dispatched when setting a new default organization (database):

```javascript
Livewire.on('default-organization-changed', (data) => {
    console.log('New default:', data.organizationId);
});
```

## Integration with Auth

### Login Event

Sync the default organization to session on login:

```php
// In your LoginController or Listener
use Illuminate\Auth\Events\Login;

class SyncOrganizationOnLogin
{
    public function handle(Login $event): void
    {
        $event->user->syncOrganizationFromDefault();
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    Login::class => [
        SyncOrganizationOnLogin::class,
    ],
];
```

### Logout Event

Clear the organization session on logout:

```php
use Illuminate\Auth\Events\Logout;

class ClearOrganizationOnLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            $event->user->clearOrganizationSession();
        }
    }
}
```

## Queue Jobs and Console Commands

Since session is not available in queue jobs and console commands,
organization context must be passed explicitly:

### Queue Jobs

```php
class ProcessOrganizationData implements ShouldQueue
{
    public function __construct(
        public int $organizationId,
        public array $data
    ) {}

    public function handle(): void
    {
        // Use the explicit organization ID
        $organization = Organization::find($this->organizationId);

        // Or use scope
        $records = ScopedModel::forOrganization($this->organizationId)->get();
    }
}
```

### Console Commands

```php
// Pass organization explicitly
php artisan organization:process --organization=123
```

## Global Scope Behavior

The `OrganizationScope` also follows the hybrid approach:

```php
// Checks session first, then database
$posts = Post::all(); // Scoped to current organization

// Bypass scope for specific organization
$posts = Post::forOrganization($orgId)->get();

// Bypass scope entirely
$posts = Post::allOrganizations()->get();
```

## Migration from Database-Only Approach

If upgrading from a database-only implementation:

1. **No schema changes required** - Same `organization_id` column is used
2. **Add login/logout listeners** - To sync session with database
3. **Update custom switching logic** - Use new session-based methods

## Best Practices

1. **Always use `getOrganizationId()`** for current context
2. **Use `setDefaultOrganizationId()`** sparingly (user-initiated "Set as Default")
3. **Sync on login** with `syncOrganizationFromDefault()`
4. **Clear on logout** with `clearOrganizationSession()`
5. **Pass explicitly** in queue jobs and commands

## Related Documentation

- [Usage Guide](./01-usage.md)
- [Components & Actions](./03-components-and-actions.md)
- [Contracts](../01-architecture/02-contracts.md)
