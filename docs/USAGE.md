# Usage Guide

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

Before attempting deletion, you can check if an organization can be deleted:

```php
$eligibility = DeleteOrganization::canDelete($organization, $user);

if ($eligibility['can_delete']) {
    // Organization can be deleted
    $result = DeleteOrganization::run($organization, $user);
} else {
    // Show the reason why deletion is not allowed
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

Organizations are **permanently deleted** using `forceDelete()`, which means:

- The deletion is immediate and irreversible
- All organization data is removed from the database
- There is no soft delete recovery option
- Users must be fully aware of the consequences

## Working with Roles

### Using the OrganizationRole Enum

```php
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

// Available roles
$member = OrganizationRole::MEMBER;
$admin = OrganizationRole::ADMINISTRATOR;

// Get role information
$label = OrganizationRole::ADMINISTRATOR->label(); // "Administrator"
$description = OrganizationRole::MEMBER->description();

// Check role capabilities
if ($role->isAdmin()) {
    // User has admin privileges
}

if ($role->isMember()) {
    // User is a regular member
}

// Get all available roles
$allRoles = OrganizationRole::cases();
$roleOptions = OrganizationRole::options(); // For dropdowns
```

## Organization Settings

### Basic Settings Management

```php
$organization = Organization::find(1);

// Set individual settings
$organization->setSetting('contact.email', 'info@company.com');
$organization->setSetting('app.timezone', 'America/New_York');
$organization->setSetting('features.api_access', true);

// Get settings with defaults
$email = $organization->getSetting('contact.email');
$timezone = $organization->getSetting('app.timezone', 'UTC');

// Check if setting exists
if ($organization->hasSetting('features.api_access')) {
    // Setting exists
}

// Get all settings
$allSettings = $organization->getAllSettings();

// Remove a setting
$organization->removeSetting('contact.fax');

// Save changes
$organization->save();
```

### Bulk Settings Operations

```php
// Merge new settings with existing ones
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

// Apply default settings (for existing organizations)
$organization->applyDefaultSettings();

// Reset all settings to defaults
$organization->resetSettingsToDefaults();
```

### Working with Specific Setting Categories

#### Contact Information

```php
// Set contact details
$organization->setSetting('contact.email', 'info@company.com');
$organization->setSetting('contact.phone', '+1-234-567-8900');
$organization->setSetting('contact.website', 'https://company.com');

// Address information
$organization->setSetting('address.street', '123 Main St');
$organization->setSetting('address.city', 'New York');
$organization->setSetting('address.state', 'NY');
$organization->setSetting('address.postal_code', '10001');
$organization->setSetting('address.country', 'US');
```

#### Business Information

```php
$organization->setSetting('business.industry', 'Technology');
$organization->setSetting('business.company_size', '50-100');
$organization->setSetting('business.founded_year', 2020);
$organization->setSetting('business.tax_id', 'TAX123456');
```

#### Application Preferences

```php
$organization->setSetting('app.timezone', 'America/New_York');
$organization->setSetting('app.locale', 'en');
$organization->setSetting('app.currency', 'USD');
$organization->setSetting('app.date_format', 'Y-m-d');
$organization->setSetting('app.time_format', 'H:i:s');
```

#### Feature Toggles

```php
$organization->setSetting('features.notifications', true);
$organization->setSetting('features.analytics', true);
$organization->setSetting('features.api_access', true);
$organization->setSetting('features.custom_branding', false);
$organization->setSetting('features.multi_language', false);
```

#### UI/UX Preferences

```php
$organization->setSetting('ui.theme', 'dark');
$organization->setSetting('ui.sidebar_collapsed', true);
$organization->setSetting('ui.layout', 'compact');
$organization->setSetting('ui.items_per_page', 50);
```

#### Security Settings

```php
$organization->setSetting('security.two_factor_required', true);
$organization->setSetting('security.password_expires_days', 90);
$organization->setSetting('security.session_timeout_minutes', 60);
$organization->setSetting('security.allowed_domains', ['company.com']);
```

## Automatic Data Scoping

### User Model with Organization Relationships

The `InteractsWithUserOrganization` trait provides comprehensive user-organization relationship management. Add this trait to your User model:

```php
use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithUserOrganization;
use CleaniqueCoders\LaravelOrganization\Contracts\UserOrganizationContract;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements UserOrganizationContract
{
    use InteractsWithUserOrganization;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id', // Current organization
    ];
}
```

#### Managing User's Current Organization

```php
$user = User::find(1);

// Get current organization ID
$currentOrgId = $user->getOrganizationId();

// Set/switch to different organization
$user->setOrganizationId($organization->id);
$user->save();

// Get current organization (relationship)
$currentOrg = $user->currentOrganization;
```

#### Checking Organization Membership

```php
$user = User::find(1);

// Check if user belongs to an organization (as member or owner)
if ($user->belongsToOrganization($organizationId)) {
    // User is member or owner of this organization
}

// Check if user is the owner
if ($user->ownsOrganization($organizationId)) {
    // User owns this organization
}

// Check if user is a member
if ($user->isMemberOf($organizationId)) {
    // User is a member (includes admins and owner)
}

// Check if user is an administrator
if ($user->isAdministratorOf($organizationId)) {
    // User has admin privileges (or is owner)
}
```

#### Getting User's Organizations

```php
$user = User::find(1);

// Get all organizations user belongs to
$allOrgs = $user->organizations;

// Get only active memberships
$activeOrgs = $user->activeOrganizations;

// Get organizations user owns
$ownedOrgs = $user->ownedOrganizations;

// Get organizations where user is administrator
$adminOrgs = $user->administratedOrganizations;
```

#### Checking User Roles

```php
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

$user = User::find(1);

// Check specific role in organization
if ($user->hasRoleInOrganization($organizationId, 'administrator')) {
    // User has administrator role
}

// Works with enum too
if ($user->hasRoleInOrganization($organizationId, OrganizationRole::MEMBER->value)) {
    // User has member role
}
```

#### Accessing Pivot Data

```php
// The organizations relationship includes pivot data
foreach ($user->organizations as $organization) {
    $role = $organization->pivot->role;           // 'member' or 'administrator'
    $isActive = $organization->pivot->is_active;  // true/false
    $joinedAt = $organization->pivot->created_at;

    echo "Role: {$role}, Active: {$isActive}";
}
```

### Other Models with Organization Scoping

The package automatically applies organization scoping to models using the `InteractsWithOrganization` trait:

```php
use App\Models\User;

// Only users from authenticated user's organization (automatic)
$users = User::all();

// Users from all organizations (bypass scoping)
$allUsers = User::allOrganizations()->get();

// Users from specific organization
$orgUsers = User::forOrganization(5)->get();

// Get current organization ID
$currentOrgId = User::getCurrentOrganizationId();
```

### Adding Scoping to Your Models

```php
use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganization;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationScopingContract;
use Illuminate\Database\Eloquent\Model;

class Post extends Model implements OrganizationScopingContract
{
    use InteractsWithOrganization;

    protected $fillable = ['title', 'content', 'organization_id'];
}
```

Now your `Post` model will automatically:

- Be scoped to the authenticated user's organization
- Have `organization_id` set automatically when creating new records
- Provide scoping methods for bypassing or targeting specific organizations

### Using Scoped Models

```php
// Automatically scoped to current user's organization
$posts = Post::all();

// Get posts from all organizations
$allPosts = Post::allOrganizations()->get();

// Get posts from specific organization
$orgPosts = Post::forOrganization(3)->get();

// The organization_id is set automatically when creating
$post = Post::create([
    'title' => 'My Post',
    'content' => 'Post content',
    // organization_id is set automatically
]);
```

## Validation

### Settings Validation

Settings are automatically validated when saving:

```php
$organization = Organization::find(1);

try {
    // This will fail validation (invalid email)
    $organization->setSetting('contact.email', 'invalid-email');
    $organization->save();
} catch (\Illuminate\Validation\ValidationException $e) {
    $errors = $e->errors();
    // Handle validation errors
}
```

### Custom Validation

You can extend validation rules in your configuration:

```php
// config/organization.php
'validation_rules' => [
    // ... existing rules
    'custom.api_key' => 'required|string|min:32',
    'custom.webhook_url' => 'nullable|url',
],
```

## Service Classes and Dependency Injection

### Using Contracts in Services

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

class OrganizationService
{
    public function __construct(
        private OrganizationMembershipContract $organization
    ) {}

    public function addMember($userId, OrganizationRole $role = OrganizationRole::MEMBER): void
    {
        $user = User::find($userId);
        $this->organization->addUser($user, $role);
    }

    public function promoteToAdmin($userId): void
    {
        $user = User::find($userId);
        $this->organization->updateUserRole($user, OrganizationRole::ADMINISTRATOR);
    }
}
```

### Resolving Contracts

```php
// Contracts are automatically bound in the service container
$orgContract = app(OrganizationContract::class);
$membershipContract = app(OrganizationMembershipContract::class);
$settingsContract = app(OrganizationSettingsContract::class);
```

## Testing

### Testing with Organizations

```php
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;

public function test_user_can_access_organization_data()
{
    $organization = Organization::factory()->create();
    $user = UserFactory::new()->create(['organization_id' => $organization->id]);

    $this->actingAs($user);

    // Test organization-scoped data access
    $posts = Post::all(); // Only posts from user's organization

    $this->assertTrue($posts->every(fn($post) => $post->organization_id === $organization->id));
}
```

### Mocking Contracts

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use Mockery;

public function test_service_adds_member_correctly()
{
    $mockOrganization = Mockery::mock(OrganizationMembershipContract::class);
    $mockOrganization->shouldReceive('addUser')
        ->once()
        ->with($user, OrganizationRole::MEMBER);

    $service = new OrganizationService($mockOrganization);
    $service->addMember($user->id);
}
```
