# Authorization & Policies

The Laravel Organization package includes a comprehensive authorization layer through the `OrganizationPolicy` class, which integrates seamlessly with Laravel's Gate and Blade authorization features.

## Overview

The `OrganizationPolicy` enforces consistent authorization rules for organizations based on user roles:

- **Owner**: Full access to create, read, update, delete, and manage all aspects of an organization
- **Administrator**: Can manage organization settings, members, and view organization details
- **Member**: Can view organization details but cannot modify or manage

## Available Policy Methods

### Viewing Organizations

#### `viewAny(?User $user): bool`

Determines whether a user can view any organizations. Anyone can call this method.

```php
if (auth()->user()->can('viewAny', Organization::class)) {
    // Show list of all organizations
}
```

#### `view(?User $user, Organization $organization): bool`

Determines whether a user can view a specific organization.

**Allowed:**

- Authenticated members of the organization
- Organization administrators
- Organization owner
- Unauthenticated users: **No**

```php
// In controller or blade
if (auth()->user()->can('view', $organization)) {
    return view('organizations.show', ['organization' => $organization]);
}
```

**Blade example:**

```blade
@can('view', $organization)
    <div>{{ $organization->name }}</div>
@endcan
```

### Creating Organizations

#### `create(?User $user): bool`

Determines whether a user can create organizations.

**Allowed:**

- Any authenticated user

```php
if (auth()->user()->can('create', Organization::class)) {
    return view('organizations.create');
}
```

### Updating Organizations

#### `update(?User $user, Organization $organization): bool`

Determines whether a user can update an organization.

**Allowed:**

- Organization owner
- Organization administrators
- Others: **No**

```php
if (auth()->user()->can('update', $organization)) {
    $organization->update($validated);
}
```

**Livewire example:**

```php
public function updateOrganization()
{
    $this->authorize('update', $this->organization);

    $this->organization->update($this->validate([
        'name' => 'required|string',
        'description' => 'nullable|string',
    ]));
}
```

### Deleting Organizations

#### `delete(?User $user, Organization $organization): bool`

Determines whether a user can delete an organization.

**Allowed:**

- Organization owner only
- Others: **No**

```php
if (auth()->user()->can('delete', $organization)) {
    $organization->delete(); // Soft delete
}
```

#### `restore(?User $user, Organization $organization): bool`

Determines whether a user can restore a soft-deleted organization.

**Allowed:**

- Organization owner only

```php
if (auth()->user()->can('restore', $organization)) {
    $organization->restore();
}
```

#### `forceDelete(?User $user, Organization $organization): bool`

Determines whether a user can permanently delete an organization.

**Allowed:**

- Organization owner only

```php
if (auth()->user()->can('forceDelete', $organization)) {
    $organization->forceDelete(); // Permanent delete
}
```

### Managing Members

#### `manageMembers(?User $user, Organization $organization): bool`

Determines whether a user can manage organization members.

**Allowed:**

- Organization owner
- Organization administrators

```php
if (auth()->user()->can('manageMembers', $organization)) {
    // Show member management interface
}
```

#### `addMember(?User $user, Organization $organization): bool`

Determines whether a user can add members to an organization.

**Allowed:**

- Organization owner
- Organization administrators

```php
if (auth()->user()->can('addMember', $organization)) {
    $organization->users()->attach($newUser, ['role' => OrganizationRole::MEMBER]);
}
```

#### `removeMember(?User $user, Organization $organization): bool`

Determines whether a user can remove members from an organization.

**Allowed:**

- Organization owner
- Organization administrators

```php
if (auth()->user()->can('removeMember', $organization)) {
    $organization->users()->detach($member);
}
```

#### `changeMemberRole(?User $user, Organization $organization): bool`

Determines whether a user can change member roles.

**Allowed:**

- Organization owner
- Organization administrators

```php
if (auth()->user()->can('changeMemberRole', $organization)) {
    $organization->users()->updateExistingPivot($member, [
        'role' => OrganizationRole::ADMINISTRATOR,
    ]);
}
```

### Organization Ownership

#### `transferOwnership(?User $user, Organization $organization): bool`

Determines whether a user can transfer organization ownership.

**Allowed:**

- Organization owner only

```php
if (auth()->user()->can('transferOwnership', $organization)) {
    $organization->update(['owner_id' => $newOwner->id]);
}
```

### Organization Settings

#### `manageSettings(?User $user, Organization $organization): bool`

Determines whether a user can manage organization settings.

**Allowed:**

- Organization owner
- Organization administrators

```php
if (auth()->user()->can('manageSettings', $organization)) {
    $organization->updateSettings(['timezone' => 'America/New_York']);
}
```

## Usage Examples

### In Controllers

```php
<?php

namespace App\Http\Controllers;

use CleaniqueCoders\LaravelOrganization\Models\Organization;

class OrganizationController extends Controller
{
    public function edit(Organization $organization)
    {
        // Authorize using the policy
        $this->authorize('update', $organization);

        return view('organizations.edit', ['organization' => $organization]);
    }

    public function update(Organization $organization)
    {
        $this->authorize('update', $organization);

        $organization->update(request()->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]));

        return redirect()->route('organizations.show', $organization);
    }

    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return redirect()->route('organizations.index');
    }
}
```

### In Blade Templates

```blade
<!-- Show edit button only to authorized users -->
@can('update', $organization)
    <a href="{{ route('organizations.edit', $organization) }}" class="btn btn-primary">
        Edit Organization
    </a>
@endcan

<!-- Show delete button only to owner -->
@can('delete', $organization)
    <form method="POST" action="{{ route('organizations.destroy', $organization) }}" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">
            Delete
        </button>
    </form>
@endcan

<!-- Show member management only to authorized users -->
@can('manageMembers', $organization)
    <a href="{{ route('organizations.members.index', $organization) }}" class="btn btn-secondary">
        Manage Members
    </a>
@endcan
```

### In Livewire Components

```php
<?php

namespace App\Livewire;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Livewire\Component;

class UpdateOrganization extends Component
{
    public Organization $organization;
    public string $name;
    public ?string $description = null;

    public function mount()
    {
        // Authorize access when mounting the component
        $this->authorize('update', $this->organization);

        $this->name = $this->organization->name;
        $this->description = $this->organization->description;
    }

    public function update()
    {
        // Double-check authorization before saving
        $this->authorize('update', $this->organization);

        $this->organization->update($this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]));

        session()->flash('success', 'Organization updated successfully!');
    }

    public function render()
    {
        return view('livewire.update-organization');
    }
}
```

### Using Gate Directly

```php
use Illuminate\Support\Facades\Gate;
use CleaniqueCoders\LaravelOrganization\Models\Organization;

// Check authorization manually
if (Gate::allows('update', $organization)) {
    // User can update
}

// Get authorization result
$canUpdate = auth()->user()->can('update', $organization);
$canDelete = auth()->user()->can('delete', $organization);

// Require authorization (throw 403 on failure)
auth()->user()->authorize('delete', $organization);
```

## Extending the Policy

You can extend the `OrganizationPolicy` to add custom authorization logic for your application:

```php
<?php

namespace App\Policies;

use CleaniqueCoders\LaravelOrganization\Policies\OrganizationPolicy as BasePolicy;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;

class OrganizationPolicy extends BasePolicy
{
    /**
     * Custom method to check if user can export organization data
     */
    public function export(?User $user, Organization $organization): bool
    {
        // Allow export for organization administrators and owner
        return $this->manageSettings($user, $organization);
    }

    /**
     * Custom method to check if user can publish organization
     */
    public function publish(?User $user, Organization $organization): bool
    {
        // Only owner can publish
        if ($user === null) {
            return false;
        }

        return $organization->owner_id === $user->id;
    }

    /**
     * Override the base update method with custom logic
     */
    public function update(?User $user, Organization $organization): bool
    {
        // First, check base authorization
        if (! parent::update($user, $organization)) {
            return false;
        }

        // Add custom business logic
        if ($organization->isFrozen()) {
            return false; // Cannot edit frozen organizations
        }

        return true;
    }
}
```

Then register your custom policy in your application's service provider:

```php
use Illuminate\Support\Facades\Gate;
use App\Policies\OrganizationPolicy;
use CleaniqueCoders\LaravelOrganization\Models\Organization;

public function boot()
{
    Gate::policy(Organization::class, OrganizationPolicy::class);
}
```

## Role-Based Access Control Reference

| Action | Owner | Admin | Member | Guest |
|--------|-------|-------|--------|-------|
| View Organization | ✓ | ✓ | ✓ | ✗ |
| Create Organization | ✓ | ✓ | ✓ | ✗ |
| Update Organization | ✓ | ✓ | ✗ | ✗ |
| Delete Organization | ✓ | ✗ | ✗ | ✗ |
| Restore Organization | ✓ | ✗ | ✗ | ✗ |
| Force Delete | ✓ | ✗ | ✗ | ✗ |
| Manage Members | ✓ | ✓ | ✗ | ✗ |
| Add Members | ✓ | ✓ | ✗ | ✗ |
| Remove Members | ✓ | ✓ | ✗ | ✗ |
| Change Member Roles | ✓ | ✓ | ✗ | ✗ |
| Transfer Ownership | ✓ | ✗ | ✗ | ✗ |
| Manage Settings | ✓ | ✓ | ✗ | ✗ |

## Best Practices

1. Authorize early in controllers and components
2. Use Blade `@can`/`@cannot` directives in views
3. Double-check in Livewire in both `mount()` and action methods
4. Extend base policy thoughtfully (call `parent::` methods)
5. Consider logging failed authorization attempts

## Testing Authorization

```php
$this->actingAs($organization->owner)
    ->patch(route('organizations.update', $organization), ['name' => 'New Name'])
    ->assertSuccessful();

$this->actingAs($member)
    ->patch(route('organizations.update', $organization), ['name' => 'New Name'])
    ->assertForbidden();

$policy = new OrganizationPolicy();
$this->assertTrue($policy->delete($owner, $organization));
$this->assertFalse($policy->delete(UserFactory::new()->create(), $organization));
```
