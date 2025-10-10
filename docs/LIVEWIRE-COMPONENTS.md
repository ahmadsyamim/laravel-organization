# Livewire Components Documentation

The Laravel Organization package provides several pre-built Livewire components for managing organizations in your application. These components are built with Alpine.js for interactivity and styled with Tailwind CSS.

## Available Components

### 1. Organization Switcher (`OrganizationSwitcher`)

A dropdown component that allows users to switch between organizations they have access to.

#### Usage

```blade
{{-- Pass the authenticated user to the component --}}
<livewire:org::switcher :user="auth()->user()" />

{{-- Or using a variable --}}
<livewire:org::switcher :user="$user" />

{{-- If user is not passed, it will use Auth::user() as fallback --}}
<livewire:org::switcher />
```

#### Features

- Displays current organization with avatar
- Lists all accessible organizations
- Shows user role in each organization
- Provides quick access to create and manage functions
- Auto-updates when organizations change
- Loading states and error handling

#### Events Emitted

- `organization-switched` - When user switches to a different organization
- `show-create-organization` - Triggers create organization modal
- `show-manage-organization` - Triggers manage organization modal

---

### 2. Create Organization Form (`CreateOrganizationForm`)

A modal form for creating new organizations with validation.

#### Usage

```blade
<livewire:org::form />
```

#### Features

- Modal-based form with Alpine.js animations
- Real-time validation
- Option to set as current organization
- Character counter for description
- Loading states and success/error messages

#### Events Listened

- `show-create-organization` - Opens the modal

#### Events Emitted

- `organization-created` - When organization is successfully created
- `organization-switched` - When "set as current" option is enabled

---

### 3. Manage Organization (`ManageOrganization`)

A comprehensive modal for editing and deleting organizations.

#### Usage

```blade
<livewire:org::manage />
```

#### Features

- Edit organization name and description
- Delete organization with confirmation
- Permission checks (owner/admin only)
- Safe deletion (prevents deletion with active members)
- Confirmation name input for deletion

#### Events Listened

- `show-manage-organization` - Opens the modal with organization data

#### Events Emitted

- `organization-updated` - When organization is successfully updated
- `organization-deleted` - When organization is successfully deleted

---

### 4. Organization List (`OrganizationList`)

A full-featured table for displaying and managing all user organizations.

#### Usage

```blade
<livewire:org::list />
```

#### Features

- Paginated table with search and filtering
- Sortable columns (name, created date)
- Filter by role (all, owned, member)
- Quick actions (switch, edit, delete)
- Responsive design
- Empty states with helpful messages

#### Events Listened

- `organization-created` - Refreshes the list
- `organization-updated` - Refreshes the list
- `organization-deleted` - Refreshes the list

#### Events Emitted

- `show-manage-organization` - Triggers edit/delete modals
- `organization-switched` - When switching organizations

---

### 5. Organization Widget (`OrganizationWidget`)

A compact sidebar widget showing current organization and quick actions.

#### Usage

```blade
<livewire:org::widget />

<!-- With options -->
<livewire:org::widget :show-quick-actions="false" />
```

#### Parameters

- `showQuickActions` (boolean, default: true) - Shows/hides action buttons

#### Features

- Current organization display
- Recent organizations list
- Quick actions (create, manage, view all)
- Organization statistics
- Compact design for sidebars

## Complete Implementation Example

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

## Event System

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

## Customization

### Styling

All components use Tailwind CSS classes and can be customized by:

1. **Publishing the views:**

```bash
php artisan vendor:publish --tag="org-views"
```

2. **Modifying the published Blade templates** in `resources/views/vendor/laravel-organization/`

### Extending Components

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

## Requirements

- Laravel 11+
- Livewire 3.0+
- Alpine.js 3.0+
- Tailwind CSS 3.0+

## Installation

1. Add Livewire to your project if not already installed:

```bash
composer require livewire/livewire
```

2. Install the organization package:

```bash
composer require cleaniquecoders/laravel-organization
```

3. Publish and run migrations:

```bash
php artisan vendor:publish --tag="org-migrations"
php artisan migrate
```

4. Optionally publish views for customization:

```bash
php artisan vendor:publish --tag="org-views"
```

## Configuration

The components work with the organization configuration. Make sure your User model has the appropriate relationships and current organization tracking if needed.

Example User model additions:

```php
class User extends Authenticatable
{
    protected $fillable = [
        // ... other fields
        'current_organization_id',
    ];

    public function currentOrganization()
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
                    ->withPivot(['role', 'is_active'])
                    ->withTimestamps();
    }
}
```
