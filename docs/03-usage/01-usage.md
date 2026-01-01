# Usage Guide

(Original content migrated from usage.md; cross-links updated.)

## Quick Start

### Creating Organizations

#### Programmatically

Use the `CreateNewOrganization` action:

```php
use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use App\Models\User;

$user = User::find(1);
$action = new CreateNewOrganization();

// Create a default organization for the user
$organization = $action->handle($user);

// Create an additional organization with custom details
$customOrg = $action->handle(
    user: $user,
    default: false,
    customName: 'My Company',
    customDescription: 'A great company'
);
```

#### Using Artisan Commands

```bash
# Create default organization for user
php artisan user:create-org user@example.com

# Create with custom name
php artisan user:create-org user@example.com --organization_name="My Company"

# Create with custom name and description
php artisan user:create-org user@example.com --organization_name="My Company" --description="A great company"
```

## Working with Organizations

### Basic Organization Operations

```php
use CleaniqueCoders\LaravelOrganization\Models\Organization;

$organization = Organization::find(1);

// Get organization details
$name = $organization->getName();
$slug = $organization->getSlug();
$uuid = $organization->getUuid();
$description = $organization->getDescription();

// Check if organization is active
if ($organization->isActive()) {
    // Organization is not soft deleted
}
```

### Ownership Management

```php
use App\Models\User;

$organization = Organization::find(1);
$user = User::find(1);
$newOwner = User::find(2);

// Check ownership
if ($organization->isOwnedBy($user)) {
    // User owns this organization
}

// Get owner ID
$ownerId = $organization->getOwnerId();

// Transfer ownership
$organization->transferOwnership($newOwner);
```

### User Membership Management

```php
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

$organization = Organization::find(1);
$user = User::find(1);

// Add users to organization
$organization->addUser($user, OrganizationRole::ADMINISTRATOR);
$organization->addUser($anotherUser, OrganizationRole::MEMBER);

// Check membership
if ($organization->hasMember($user)) {
    // User is a member
}

if ($organization->hasActiveMember($user)) {
    // User is an active member
}

// Get members by role
$administrators = $organization->administrators;
$members = $organization->members;
$allActiveMembers = $organization->allMembers();

// Manage user roles
$organization->updateUserRole($user, OrganizationRole::ADMINISTRATOR);

// Check user's role
$role = $organization->getUserRole($user);
if ($organization->userHasRole($user, OrganizationRole::ADMINISTRATOR)) {
    // User is an administrator
}

// Activate/deactivate users
$organization->setUserActiveStatus($user, false); // Deactivate
$organization->setUserActiveStatus($user, true);  // Activate

// Remove user from organization
$organization->removeUser($user);
```

### Deleting Organizations

Organizations can be permanently deleted using the `DeleteOrganization` action, but the deletion must comply with specific business rules:

```php
use CleaniqueCoders\LaravelOrganization\Actions\DeleteOrganization;

// Attempt to delete an organization
$result = DeleteOrganization::run($organization, $user);

// Check result
if ($result['success']) {
    // Organization deleted successfully
    $deletedId = $result['deleted_organization_id'];
    $deletedName = $result['deleted_organization_name'];
}
```

#### Organization Deletion Rules

Before an organization can be deleted, the following business rules must be satisfied:

1. **Owner-Only Deletion**: Only the organization owner can delete it
2. **Minimum Organization Requirement**: Users must maintain at least one organization
3. **Active Organization Protection**: Cannot delete the currently active organization
4. **Member Removal Requirement**: All active members (excluding owner) must be removed first
5. **Name Confirmation Required**: Users must type the exact organization name to confirm deletion

#### Checking Deletion Eligibility

```php
$eligibility = DeleteOrganization::canDelete($organization, $user);

if ($eligibility['can_delete']) {
    $result = DeleteOrganization::run($organization, $user);
} else {
    echo $eligibility['reason'];
}
```

#### Deletion Error Messages

| Scenario | Error Message |
|----------|--------------|
| Only one organization | "Cannot delete your only organization. You must have at least one organization." |
| Current organization | "Cannot delete your current organization. Please switch to another organization first." |
| Has active members | "Cannot delete organization with active members. Remove all members first." |
| Not owner | "Only the organization owner can delete the organization." |
| Name mismatch | "Organization name does not match." |

#### Deletion Type

Organizations are **permanently deleted** using `forceDelete()`.

## Working with Roles

```php
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

$member = OrganizationRole::MEMBER;
$admin = OrganizationRole::ADMINISTRATOR;

$label = OrganizationRole::ADMINISTRATOR->label();
$description = OrganizationRole::MEMBER->description();

if ($role->isAdmin()) { /* ... */ }
if ($role->isMember()) { /* ... */ }

$allRoles = OrganizationRole::cases();
$roleOptions = OrganizationRole::options();
```

## Organization Settings

### Basic Settings Management

```php
$organization = Organization::find(1);

$organization->setSetting('contact.email', 'info@company.com');
$organization->setSetting('app.timezone', 'America/New_York');
$organization->setSetting('features.api_access', true);

$email = $organization->getSetting('contact.email');
$timezone = $organization->getSetting('app.timezone', 'UTC');

if ($organization->hasSetting('features.api_access')) { /* ... */ }

$allSettings = $organization->getAllSettings();
$organization->removeSetting('contact.fax');
$organization->save();
```

### Bulk Settings Operations

```php
$organization->mergeSettings([
    'contact' => [
        'email' => 'new@company.com',
        'phone' => '+1-234-567-8900',
    ],
    'features' => [
        'notifications' => true,
        'api_access' => true,
    ],
]);

$organization->applyDefaultSettings();
$organization->resetSettingsToDefaults();
```

### Category Examples

(Examples kept from original document for each category.)

## Automatic Data Scoping

See architecture overview for global scope details. User model integration:

```php
use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithUserOrganization;
use CleaniqueCoders\LaravelOrganization\Contracts\UserOrganizationContract;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements UserOrganizationContract
{
    use InteractsWithUserOrganization;
}
```

### Organization Switching (Hybrid Approach)

The package uses a **hybrid session/database approach** for organization context:

- **Session**: Used for active switching (no database writes)
- **Database**: Stores the user's "default" organization

```php
// Switch organization (session only - no DB write)
$user->setOrganizationId($organizationId);

// Set as default (writes to database)
$user->setDefaultOrganizationId($organizationId);

// Get current organization (session first, then database)
$currentOrgId = $user->getOrganizationId();

// Get default organization (database only)
$defaultOrgId = $user->getDefaultOrganizationId();

// On login: sync default to session
$user->syncOrganizationFromDefault();

// On logout: clear session
$user->clearOrganizationSession();
```

See [Organization Switching](./05-organization-switching.md) for detailed documentation.

Membership checks and role helpers remain identical to original usage guide.

## Validation

Settings are validated automatically; extend rules in `config/organization.php`.

## Service Classes & DI

Contracts resolve via container: `app(OrganizationContract::class);`

## Testing Snippet

```php
$organization = Organization::factory()->create();
$user = User::factory()->create(['organization_id' => $organization->id]);
$this->actingAs($user);
$posts = Post::all();
$this->assertTrue($posts->every(fn($p) => $p->organization_id === $organization->id));
```
