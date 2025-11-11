# Organization Contracts

This package provides contracts (interfaces) that follow SOLID principles to ensure flexible and maintainable organization management. These contracts allow developers to implement their own models while ensuring they provide all necessary functionality.

## Available Contracts

### 1. OrganizationContract

The core contract that defines essential organization identity and basic functionality.

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;

class CustomOrganization extends Model implements OrganizationContract
{
    // Must implement:
    public function getId();
    public function getUuid(): string;
    public function getName(): string;
    public function getSlug(): string;
    public function getDescription(): ?string;
    public function owner(): BelongsTo;
    public function isActive(): bool;
}
```

### 2. OrganizationMembershipContract

Manages user membership within organizations.

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;

class CustomOrganization extends Model implements OrganizationMembershipContract
{
    // Must implement user management methods:
    public function users(): BelongsToMany;
    public function activeUsers(): BelongsToMany;
    public function administrators(): BelongsToMany;
    public function members(): BelongsToMany;
    public function allMembers();
    public function hasMember(User $user): bool;
    public function hasActiveMember(User $user): bool;
    public function addUser(User $user, OrganizationRole $role = OrganizationRole::MEMBER, bool $isActive = true): void;
    public function removeUser(User $user): void;
    public function updateUserRole(User $user, OrganizationRole $role): void;
    public function setUserActiveStatus(User $user, bool $isActive): void;
    public function getUserRole(User $user): ?OrganizationRole;
    public function userHasRole(User $user, OrganizationRole $role): bool;
}
```

### 3. OrganizationOwnershipContract

Manages organization ownership.

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;

class CustomOrganization extends Model implements OrganizationOwnershipContract
{
    // Must implement ownership methods:
    public function getOwnerId();
    public function setOwner(User $user): void;
    public function isOwnedBy(User $user): bool;
    public function transferOwnership(User $newOwner): void;
}
```

### 4. OrganizationSettingsContract

Manages organization settings and configuration.

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;

class CustomOrganization extends Model implements OrganizationSettingsContract
{
    // Must implement settings methods:
    public function applyDefaultSettings(): void;
    public static function getDefaultSettings(): array;
    public function validateSettings(): void;
    public function resetSettingsToDefaults(): void;
    public function mergeSettings(array $newSettings): void;
    public function getSetting(string $key, $default = null);
    public function setSetting(string $key, $value): void;
    public function hasSetting(string $key): bool;
    public function removeSetting(string $key): void;
    public function getAllSettings(): array;
}
```

### 5. OrganizationScopingContract

For models that need to be scoped to organizations (multi-tenancy).

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationScopingContract;

class ScopedModel extends Model implements OrganizationScopingContract
{
    // Must implement scoping methods:
    public function organization(): BelongsTo;
    public function scopeAllOrganizations(Builder $query): Builder;
    public function scopeForOrganization(Builder $query, $organizationId): Builder;
    public static function getCurrentOrganizationId(): ?int;
    public function getOrganizationId();
}
```

### 6. UserOrganizationContract

For user models that interact with organizations.

```php
use CleaniqueCoders\LaravelOrganization\Contracts\UserOrganizationContract;

class CustomUser extends User implements UserOrganizationContract
{
    // Must implement user-organization methods:
    public function getOrganizationId();
    public function setOrganizationId($organizationId): void;
    public function belongsToOrganization($organizationId): bool;
    public function organizations();
    public function ownedOrganizations();
}
```

## Using Contracts

### Dependency Injection

The service provider automatically binds contracts to the configured organization model:

```php
// These will resolve to your configured organization model
app(OrganizationContract::class);
app(OrganizationMembershipContract::class);
app(OrganizationOwnershipContract::class);
app(OrganizationSettingsContract::class);
```

### Service Classes

Use contracts in your service classes for better testability:

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;

class OrganizationService
{
    public function __construct(
        private OrganizationMembershipContract $organization
    ) {}

    public function addMember(User $user, OrganizationRole $role = OrganizationRole::MEMBER): void
    {
        $this->organization->addUser($user, $role);
    }
}
```

### Custom Implementations

Create your own organization model that implements the contracts:

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;

class MyCustomOrganization extends Model implements
    OrganizationContract,
    OrganizationMembershipContract,
    OrganizationOwnershipContract,
    OrganizationSettingsContract
{
    // Implement all required methods...

    // You can also use the provided traits:
    use InteractsWithOrganizationSettings;

    // And add your own custom functionality:
    public function getCustomAttribute(): string
    {
        return 'Custom implementation';
    }
}
```

Then update your configuration:

```php
// config/organization.php
return [
    'organization-model' => MyCustomOrganization::class,
    // ... other config
];
```

## SOLID Principles Compliance

### Single Responsibility Principle (SRP)

Each contract has a single, well-defined responsibility:

- `OrganizationContract`: Core organization identity
- `OrganizationMembershipContract`: User membership management
- `OrganizationOwnershipContract`: Ownership management
- `OrganizationSettingsContract`: Settings management
- `OrganizationScopingContract`: Multi-tenancy scoping
- `UserOrganizationContract`: User-side organization interactions

### Open/Closed Principle (OCP)

The contracts are open for extension but closed for modification. You can implement your own models that extend functionality without changing the contracts.

### Liskov Substitution Principle (LSP)

Any implementation of these contracts can be substituted for another without affecting the application's correctness.

### Interface Segregation Principle (ISP)

Contracts are segregated by functionality, so implementations only need to depend on the interfaces they actually use.

### Dependency Inversion Principle (DIP)

High-level modules depend on abstractions (contracts) rather than concrete implementations, making the system more flexible and testable.

## Testing with Contracts

Contracts make testing easier by allowing you to mock implementations:

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;

test('service adds member correctly', function () {
    $mockOrganization = Mockery::mock(OrganizationMembershipContract::class);
    $mockOrganization->shouldReceive('addUser')
        ->once()
        ->with($user, OrganizationRole::MEMBER);

    $service = new OrganizationService($mockOrganization);
    $service->addMember($user);
});
```
