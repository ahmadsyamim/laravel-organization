# Components & Actions

This documentation covers the action classes and Livewire components provided by the Laravel Organization package.

---

## Organization Actions

This package provides dedicated action classes for managing organizations. Actions are reusable, testable units of business logic that can be used in various contexts (Livewire components, API controllers, console commands, jobs, etc.).

### Available Actions

#### 1. CreateNewOrganization

Create a new organization for a user.

**Location:** `src/Actions/CreateNewOrganization.php`

**Usage:**

```php
use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;

$organization = CreateNewOrganization::run(
    $user,              // User instance
    $default,           // Boolean: is this the default organization?
    $name,             // Organization name (optional for default)
    $description       // Organization description (optional)
);
```

**Features:**

- Automatically sets as user's current organization if default
- Generates unique slug from name
- Applies default settings
- Attaches user as owner with appropriate role

#### 2. UpdateOrganization

Update an existing organization with validation.

**Location:** `src/Actions/UpdateOrganization.php`

**Usage:**

```php
use CleaniqueCoders\LaravelOrganization\Actions\UpdateOrganization;

$updatedOrganization = UpdateOrganization::run(
    $organization,      // Organization instance
    $user,             // User performing the update
    [
        'name' => 'Updated Name',
        'description' => 'Updated description',
    ]
);
```

**Features:**

- Validates user permissions (owner or administrator)
- Validates data (name uniqueness, length constraints)
- Returns fresh organization instance
- Provides static methods for rules and messages

**Validation Rules:**

- `name`: Required, 2-255 characters, unique (excluding current org)
- `description`: Optional, max 1000 characters

**Permissions:**

- Organization owner can update
- Organization administrator can update
- Other users will receive exception

**Static Helper Methods:**

```php
// Get validation rules
$rules = UpdateOrganization::rules($organization);

// Get validation messages
$messages = UpdateOrganization::messages();
```

#### 3. DeleteOrganization

Permanently delete an organization with business rule validation.

**Location:** `src/Actions/DeleteOrganization.php`

**Usage:**

```php
use CleaniqueCoders\LaravelOrganization\Actions\DeleteOrganization;

$result = DeleteOrganization::run(
    $organization,      // Organization instance
    $user              // User performing the deletion
);

// Returns array:
// [
//     'success' => true,
//     'message' => "Organization 'Name' has been permanently deleted!",
//     'deleted_organization_id' => 123,
//     'deleted_organization_name' => 'Name',
// ]
```

**Business Rules:**

1. Only owner can delete
2. User must have at least one organization
3. Cannot delete currently active organization
4. Organization must have no active members (except owner)

**Features:**

- Permanently deletes (uses `forceDelete()`)
- Returns detailed result array
- Provides helper methods for checking eligibility

**Static Helper Methods:**

```php
// Check if organization can be deleted
$result = DeleteOrganization::canDelete($organization, $user);
// Returns: ['can_delete' => bool, 'reason' => string|null]

// Get deletion requirements list
$requirements = DeleteOrganization::getDeletionRequirements();
// Returns array of requirement strings
```

### Action Pattern

All actions in this package use the [Laravel Actions](https://laravelactions.com/) package by Loris Leiva, which provides the `AsAction` trait.

#### Key Features

**1. Multiple Contexts**
Actions can be used as:

- Static method calls: `UpdateOrganization::run(...)`
- Class instantiation: `(new UpdateOrganization())->handle(...)`
- Artisan commands: `php artisan user:create-org`
- Queued jobs: `UpdateOrganization::dispatch(...)`

**2. Testability**
Actions are easy to test in isolation:

```php
it('can update organization', function () {
    $result = UpdateOrganization::run($organization, $user, $data);
    expect($result)->toBeInstanceOf(Organization::class);
});
```

**3. Reusability**
Use actions anywhere in your application:

```php
// In Livewire component
UpdateOrganization::run($this->organization, auth()->user(), $this->formData);

// In API controller
$org = UpdateOrganization::run($organization, $request->user(), $validated);

// In custom service
$this->updateAction = new UpdateOrganization();
$org = $this->updateAction->handle($organization, $user, $data);
```

### Action Usage Examples

#### Example 1: Livewire Component

```php
namespace App\Http\Livewire;

use CleaniqueCoders\LaravelOrganization\Actions\UpdateOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Livewire\Component;

class EditOrganization extends Component
{
    public Organization $organization;
    public string $name;
    public string $description;

    public function save()
    {
        try {
            $updated = UpdateOrganization::run(
                $this->organization,
                auth()->user(),
                [
                    'name' => $this->name,
                    'description' => $this->description,
                ]
            );

            session()->flash('message', 'Organization updated!');
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }
}
```

#### Example 2: API Controller

```php
namespace App\Http\Controllers\Api;

use CleaniqueCoders\LaravelOrganization\Actions\UpdateOrganization;
use CleaniqueCoders\LaravelOrganization\Actions\DeleteOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function update(Request $request, Organization $organization)
    {
        try {
            $validated = $request->validate(
                UpdateOrganization::rules($organization),
                UpdateOrganization::messages()
            );

            $updated = UpdateOrganization::run(
                $organization,
                $request->user(),
                $validated
            );

            return response()->json([
                'success' => true,
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function destroy(Request $request, Organization $organization)
    {
        // Check eligibility first
        $eligibility = DeleteOrganization::canDelete($organization, $request->user());

        if (!$eligibility['can_delete']) {
            return response()->json([
                'success' => false,
                'message' => $eligibility['reason'],
            ], 400);
        }

        try {
            $result = DeleteOrganization::run($organization, $request->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

#### Example 3: Custom Service Class

```php
namespace App\Services;

use CleaniqueCoders\LaravelOrganization\Actions\UpdateOrganization;
use CleaniqueCoders\LaravelOrganization\Actions\DeleteOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;

class OrganizationManagementService
{
    public function bulkUpdate(array $organizations, User $user, array $data): array
    {
        $results = [];

        foreach ($organizations as $organization) {
            try {
                $results[] = UpdateOrganization::run($organization, $user, $data);
            } catch (\Exception $e) {
                $results[] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function deleteWithConfirmation(Organization $organization, User $user, string $confirmationName): array
    {
        // Check confirmation
        if ($organization->name !== $confirmationName) {
            return [
                'success' => false,
                'message' => 'Organization name does not match.',
            ];
        }

        // Check eligibility
        $eligibility = DeleteOrganization::canDelete($organization, $user);
        if (!$eligibility['can_delete']) {
            return [
                'success' => false,
                'message' => $eligibility['reason'],
            ];
        }

        // Perform deletion
        return DeleteOrganization::run($organization, $user);
    }
}
```

---

## Livewire Components

The Laravel Organization package provides several pre-built Livewire components for managing organizations in your application. These components are built with Alpine.js for interactivity and styled with Tailwind CSS.

### Available Components

#### 1. Organization Switcher (`OrganizationSwitcher`)

A dropdown component that allows users to switch between organizations they have access to.

**Usage:**

```blade
{{-- Pass the authenticated user to the component --}}
<livewire:org::switcher :user="auth()->user()" />

{{-- Or using a variable --}}
<livewire:org::switcher :user="$user" />

{{-- If user is not passed, it will use Auth::user() as fallback --}}
<livewire:org::switcher />
```

**Features:**

- Displays current organization with avatar
- Lists all accessible organizations
- Shows user role in each organization
- Provides quick access to create and manage functions
- Auto-updates when organizations change
- Loading states and error handling

**Events Emitted:**

- `organization-switched` - When user switches to a different organization
- `show-create-organization` - Triggers create organization modal
- `show-manage-organization` - Triggers manage organization modal

---

#### 2. Create Organization Form (`CreateOrganizationForm`)

A modal form for creating new organizations with validation.

**Usage:**

```blade
<livewire:org::form />
```

**Features:**

- Modal-based form with Alpine.js animations
- Real-time validation
- Option to set as current organization
- Character counter for description
- Loading states and success/error messages

**Events Listened:**

- `show-create-organization` - Opens the modal

**Events Emitted:**

- `organization-created` - When organization is successfully created
- `organization-switched` - When "set as current" option is enabled

---

#### 3. Manage Organization (`ManageOrganization`)

A comprehensive modal for editing and deleting organizations.

**Usage:**

```blade
<livewire:org::manage />
```

**Features:**

- Edit organization name and description
- Delete organization with confirmation
- Permission checks (owner/admin only)
- Safe deletion (prevents deletion with active members)
- Confirmation name input for deletion

**Events Listened:**

- `show-manage-organization` - Opens the modal with organization data

**Events Emitted:**

- `organization-updated` - When organization is successfully updated
- `organization-deleted` - When organization is successfully deleted

---

#### 4. Organization List (`OrganizationList`)

A full-featured table for displaying and managing all user organizations.

**Usage:**

```blade
<livewire:org::list />
```

**Features:**

- Paginated table with search and filtering
- Sortable columns (name, created date)
- Filter by role (all, owned, member)
- Quick actions (switch, edit, delete)
- Responsive design
- Empty states with helpful messages

**Events Listened:**

- `organization-created` - Refreshes the list
- `organization-updated` - Refreshes the list
- `organization-deleted` - Refreshes the list

**Events Emitted:**

- `show-manage-organization` - Triggers edit/delete modals
- `organization-switched` - When switching organizations

---

#### 5. Organization Widget (`OrganizationWidget`)

A compact sidebar widget showing current organization and quick actions.

**Usage:**

```blade
<livewire:org::widget />

<!-- With options -->
<livewire:org::widget :show-quick-actions="false" />
```

**Parameters:**

- `showQuickActions` (boolean, default: true) - Shows/hides action buttons

**Features:**

- Current organization display
- Recent organizations list
- Quick actions (create, manage, view all)
- Organization statistics
- Compact design for sidebars

### Complete Implementation Example

Here's a complete example showing how to use all components together:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Management</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @livewireStyles
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-md">
            <div class="p-4">
                <livewire:org::widget />
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1">
            <!-- Header with Organization Switcher -->
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16 items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
                        <livewire:org::switcher />
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <livewire:org::list />
                </div>
            </main>
        </div>
    </div>

    <!-- Global Modals -->
    <livewire:org::form />
    <livewire:org::manage />

    @livewireScripts
</body>
</html>
```

### Event System

The components communicate through Livewire's event system:

```javascript
// Listen for events in your JavaScript
document.addEventListener('livewire:init', () => {
    Livewire.on('organization-switched', (event) => {
        console.log('Switched to organization:', event.organizationId);
        // Update other UI elements, redirect, etc.
    });

    Livewire.on('organization-created', (event) => {
        console.log('Created organization:', event.organizationId);
        // Show success animation, redirect, etc.
    });
});
```

### Customization

#### Styling

All components use Tailwind CSS classes and can be customized by:

1. **Publishing the views:**

```bash
php artisan vendor:publish --tag="org-views"
```

2. **Modifying the published Blade templates** in `resources/views/vendor/laravel-organization/`

#### Extending Components

You can extend the components by creating your own Livewire components that extend the package components:

```php
<?php

namespace App\Http\Livewire;

use CleaniqueCoders\LaravelOrganization\Livewire\OrganizationSwitcher as BaseOrganizationSwitcher;

class CustomOrganizationSwitcher extends BaseOrganizationSwitcher
{
    public function customMethod()
    {
        // Your custom logic
    }
}
```

---

## Requirements

- Laravel 11+
- Livewire 3.0+ (for components)
- Alpine.js 3.0+ (for components)
- Tailwind CSS 3.0+ (for components)

## Testing Actions

### Testing UpdateOrganization

```php
it('can update organization with valid data', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);

    $result = UpdateOrganization::run($organization, $user, [
        'name' => 'New Name',
        'description' => 'New description',
    ]);

    expect($result->name)->toBe('New Name')
        ->and($result->description)->toBe('New description');
});
```

### Testing DeleteOrganization

```php
it('can delete organization when rules are met', function () {
    $user = User::factory()->create();
    $org1 = Organization::factory()->create(['owner_id' => $user->id]);
    $org2 = Organization::factory()->create(['owner_id' => $user->id]);

    $result = DeleteOrganization::run($org2, $user);

    expect($result['success'])->toBeTrue()
        ->and(Organization::find($org2->id))->toBeNull();
});
```

## Best Practices

### 1. Use Static Helper Methods

```php
// Get validation rules for forms
$rules = UpdateOrganization::rules($organization);

// Check deletion eligibility before attempting
$eligibility = DeleteOrganization::canDelete($organization, $user);
if (!$eligibility['can_delete']) {
    // Show user the reason
    return back()->withErrors(['organization' => $eligibility['reason']]);
}
```

### 2. Handle Exceptions Gracefully

```php
try {
    $result = UpdateOrganization::run($organization, $user, $data);
} catch (\Illuminate\Validation\ValidationException $e) {
    // Show validation errors to user
    return back()->withErrors($e->errors());
} catch (\Exception $e) {
    // Show permission/business rule errors
    return back()->with('error', $e->getMessage());
}
```

### 3. Use in Services for Complex Logic

```php
// Create a service that uses actions
class OrganizationService
{
    public function transferAndDelete(Organization $from, Organization $to, User $user)
    {
        // Transfer members
        $from->users->each(fn($member) => $to->users()->attach($member));

        // Delete old organization
        return DeleteOrganization::run($from, $user);
    }
}
```

### 4. Extend Actions for Custom Behavior

```php
namespace App\Actions;

use CleaniqueCoders\LaravelOrganization\Actions\UpdateOrganization as BaseUpdateOrganization;

class UpdateOrganization extends BaseUpdateOrganization
{
    public function handle($organization, $user, array $data)
    {
        // Add custom logic before update
        $this->logUpdate($organization, $data);

        // Call parent
        $result = parent::handle($organization, $user, $data);

        // Add custom logic after update
        $this->notifyTeam($result);

        return $result;
    }
}
```

## Related Documentation

- [Usage Guide](usage.md)
- [Configuration Guide](configuration.md)
- [Laravel Actions Package](https://laravelactions.com/)
