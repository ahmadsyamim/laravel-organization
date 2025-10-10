# Organization Actions

This package provides dedicated action classes for managing organizations. Actions are reusable, testable units of business logic that can be used in various contexts (Livewire components, API controllers, console commands, jobs, etc.).

## Available Actions

### 1. CreateNewOrganization
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

### 2. UpdateOrganization
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

### 3. DeleteOrganization
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

## Action Pattern

All actions in this package use the [Laravel Actions](https://laravelactions.com/) package by Loris Leiva, which provides the `AsAction` trait.

### Key Features

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

## Usage Examples

### Example 1: Livewire Component

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

### Example 2: API Controller

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

### Example 3: Custom Service Class

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

### Example 4: Job Queue

```php
namespace App\Jobs;

use CleaniqueCoders\LaravelOrganization\Actions\DeleteOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteInactiveOrganizations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $inactiveOrganizations = Organization::where('last_activity', '<', now()->subMonths(6))->get();

        foreach ($inactiveOrganizations as $organization) {
            $owner = $organization->owner;

            // Check if can delete
            $eligibility = DeleteOrganization::canDelete($organization, $owner);

            if ($eligibility['can_delete']) {
                try {
                    DeleteOrganization::run($organization, $owner);
                    \Log::info("Deleted inactive organization: {$organization->name}");
                } catch (\Exception $e) {
                    \Log::error("Failed to delete organization {$organization->id}: {$e->getMessage()}");
                }
            }
        }
    }
}
```

## Error Handling

### UpdateOrganization Exceptions

```php
try {
    UpdateOrganization::run($organization, $user, $data);
} catch (\Illuminate\Validation\ValidationException $e) {
    // Handle validation errors
    $errors = $e->errors();
} catch (\Exception $e) {
    // Handle permission errors
    // Message: "You do not have permission to update this organization."
}
```

### DeleteOrganization Exceptions

```php
try {
    DeleteOrganization::run($organization, $user);
} catch (\Exception $e) {
    // Possible messages:
    // - "Only the organization owner can delete the organization."
    // - "Cannot delete your only organization. You must have at least one organization."
    // - "Cannot delete your current organization. Please switch to another organization first."
    // - "Cannot delete organization with active members. Remove all members first."
}
```

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

## Migration from Direct Model Usage

### Before (Direct Model Updates)
```php
$organization->update([
    'name' => $request->name,
    'description' => $request->description,
]);
```

### After (Using Actions)
```php
UpdateOrganization::run($organization, auth()->user(), [
    'name' => $request->name,
    'description' => $request->description,
]);
```

### Benefits
- ✅ Consistent validation across application
- ✅ Permission checks enforced
- ✅ Business rules centralized
- ✅ Easy to test
- ✅ Reusable in multiple contexts
- ✅ Can be queued if needed
- ✅ Can be used in console commands

## Related Documentation

- [CreateNewOrganization](./USAGE.md#creating-organizations)
- [Organization Deletion Rules](./ORGANIZATION_DELETION_RULES.md)
- [Laravel Actions Package](https://laravelactions.com/)
