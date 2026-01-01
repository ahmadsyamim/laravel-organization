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
        if ($organization->name !== $confirmationName) {
            return [
                'success' => false,
                'message' => 'Organization name does not match.',
            ];
        }

        $eligibility = DeleteOrganization::canDelete($organization, $user);
        if (!$eligibility['can_delete']) {
            return [
                'success' => false,
                'message' => $eligibility['reason'],
            ];
        }

        return DeleteOrganization::run($organization, $user);
    }
}
```

---

## Livewire Components

The Laravel Organization package provides several pre-built Livewire components for managing organizations in your application. These components are built with Alpine.js for interactivity and styled with Tailwind CSS.

### Available Components

#### 1. Organization Switcher (`OrganizationSwitcher`)

```blade
<livewire:org::switcher :user="auth()->user()" />
```

Features include:

- Current organization display with "(Default)" badge
- Organization list with role badges
- **Switch** action (session-based, no DB write)
- **Set as Default** button (persists to database)
- Events: `organization-switched`, `default-organization-changed`

The switcher uses a **hybrid session/database approach**:

- Switching only updates the session (fast, no DB writes)
- "Set as Default" persists to database for next login

#### 2. Create Organization Form (`CreateOrganizationForm`)

```blade
<livewire:org::form />
```

Modal form with validation, set-as-current option, and event emissions.

#### 3. Manage Organization (`ManageOrganization`)

```blade
<livewire:org::manage />
```

Edit/delete with confirmation, permission checks, and events.

#### 4. Organization List (`OrganizationList`)

```blade
<livewire:org::list />
```

Paginated, sortable, filterable table with:

- "Current" and "Default" badges per organization
- **Switch To** action (session-based)
- **Set Default** action (database persist)
- Edit and Delete actions (with permissions)
- Events: `organization-switched`, `default-organization-changed`

#### 5. Organization Widget (`OrganizationWidget`)

```blade
<livewire:org::widget />
```

Compact sidebar widget with quick actions.

### Event System

JS listening example using Livewire events for `organization-switched`, `organization-created`, etc.

### Customization

- Publish views: `php artisan vendor:publish --tag="org-views"`
- Extend components by subclassing package components.

## Requirements

- Laravel 11+
- Livewire 3+
- Alpine.js 3+
- Tailwind CSS 3+

## Testing Actions

Short examples for testing `UpdateOrganization` and `DeleteOrganization`.

## Best Practices

- Use static helpers from actions for rules and eligibility checks
- Handle exceptions gracefully in UI layers
- Encapsulate complex flows in service classes

## Related Documentation

- [Usage Guide](./01-usage.md)
- [Configuration Guide](../02-development/02-configuration.md)
- [Laravel Actions Package](https://laravelactions.com/)
