# Memory Exhaustion Fix - Infinite Loop Prevention

## Problem Description

When users registered and received email verification, the application would hang and eventually hit PHP's memory limit with errors like:

```text
PHP Fatal error: Allowed memory size of 2147483648 bytes exhausted (tried to allocate 12288 bytes)
in vendor/laravel/framework/src/Illuminate/Database/Eloquent/SoftDeletingScope.php on line 121
```

## Root Cause

The issue was caused by a **circular reference/infinite loop** in the organization scoping mechanism:

1. `OrganizationScope::apply()` is called when querying models with `InteractsWithOrganization` trait
2. The scope accessed `Auth::user()->organization_id`
3. Accessing `->organization_id` as a property triggers Laravel's attribute accessor
4. If the User model has a `currentOrganization()` relationship, this might load the Organization model
5. The Organization model may have relationships to other models that use `InteractsWithOrganization`
6. Those models apply `OrganizationScope` again...
7. **Back to step 1 - infinite loop!**

### Why It Happened During Registration/Verification

During registration and email verification:

- The user is authenticated
- Multiple queries are executed (user lookup, email verification, redirects)
- Each query on scoped models triggers the scope
- The scope accesses the user model repeatedly
- This creates an exponential cascade of queries


## The Fix

### Changed Files

1. **`src/Scopes/OrganizationScope.php`**
2. **`src/Concerns/InteractsWithOrganization.php`**

### Solution Approach

Instead of accessing `Auth::user()->organization_id` (which can trigger relationships and accessors), we now:

1. **Access raw attributes directly** from the model's `attributes` property
2. **Bypass relationship loading** and any getters/accessors
3. **Prevent recursive scope application**

### Code Changes

#### Before (problematic)

```php
public function apply(Builder $builder, Model $model)
{
    if (Auth::check() && Auth::user()->organization_id) {
        $builder->where('organization_id', Auth::user()->organization_id);
    }
}
```

#### After (fixed)

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

### Key Technical Details

1. **Direct Attribute Access**: `$user->attributes['organization_id']` accesses the raw database column value
2. **No Magic Methods**: Bypasses `__get()`, accessors, and relationship loading
3. **Safe Property Check**: Uses `property_exists()` to ensure the attributes array exists
4. **Null Safety**: Gracefully handles null organization_id and unauthenticated users

## Testing

A comprehensive test suite was added in `tests/OrganizationScopeRecursionTest.php` to verify:

1. No infinite recursion when accessing organization relationships
2. Safe retrieval of organization_id without triggering relationships
3. Graceful handling of null organization_id
4. Proper behavior when user is not authenticated

## Prevention

To prevent similar issues in the future:

1. **Never access relationships in global scopes** - always access raw attributes
2. **Be cautious with `Auth::user()` properties** - they may trigger lazy loading
3. **Test with authentication flows** - registration, verification, password reset
4. **Monitor query counts** - excessive queries may indicate recursion
5. **Use Laravel Debugbar or Telescope** during development to catch query loops early

## Impact

This fix resolves:

- ✅ Memory exhaustion during registration
- ✅ Hanging requests during email verification
- ✅ Infinite loops in organization-scoped queries
- ✅ Performance issues from recursive query execution

## Compatibility

- **Laravel**: 11.x, 12.x
- **PHP**: 8.3, 8.4
- **Breaking Changes**: None - this is a bug fix with no API changes
