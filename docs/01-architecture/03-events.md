# Events & Listeners

The Laravel Organization package dispatches comprehensive events throughout the organization and member lifecycle, enabling you to build reactive features like notifications, webhooks, activity logging, and real-time UI updates.

## Overview

The package dispatches **7 events** across three categories:

- **Lifecycle Events**: `OrganizationCreated`, `OrganizationUpdated`, `OrganizationDeleted`
- **Member Events**: `MemberAdded`, `MemberRemoved`, `MemberRoleChanged`
- **Ownership Events**: `OwnershipTransferred`

All events use Laravel's event system and support:

- Event listeners
- Queued listeners
- Event broadcasting
- Third-party integrations

## Implementation Notes

### Closure Variable Scoping in Tests

When testing events with `Event::assertDispatched()`, always capture outer scope variables using the `use` keyword in closures:

```php
// ✓ Correct - Variables captured with use
Event::assertDispatched(OrganizationCreated::class, function (OrganizationCreated $event) use ($organization) {
    return $event->organization->is($organization);
});

// ✗ Wrong - Variables not available in closure scope
Event::assertDispatched(OrganizationCreated::class, function (OrganizationCreated $event) {
    return $event->organization->is($organization); // $organization undefined!
});
```

### Available Dispatchable Methods

Events use Laravel's `Dispatchable` trait which provides:

- `dispatch()` - Static dispatch method
- `dispatchIf($condition)` - Conditional dispatch
- `dispatchUnless($condition)` - Conditional dispatch
- Standard Laravel queuing methods

## Organization Lifecycle Events

### OrganizationCreated

Dispatched when a new organization is created.

```php
use CleaniqueCoders\LaravelOrganization\Events\OrganizationCreated;

// Listening to the event
Event::listen(OrganizationCreated::class, function (OrganizationCreated $event) {
    $organization = $event->organization;

    // Send welcome email to owner
    Mail::send(new WelcomeToOrganization($organization));
});
```

**Event Properties:**

- `$event->organization` - The newly created Organization instance

**When Dispatched:**

- When `CreateNewOrganization` action completes
- When organization is created via API or UI

**Use Cases:**

- Send welcome emails
- Initialize organization settings
- Create default resources
- Trigger webhooks
- Log organization creation

### OrganizationUpdated

Dispatched when organization details are updated.

```php
use CleaniqueCoders\LaravelOrganization\Events\OrganizationUpdated;

Event::listen(OrganizationUpdated::class, function (OrganizationUpdated $event) {
    $organization = $event->organization;
    $changes = $event->changes; // ['name' => 'New Name', 'description' => '...']

    // Log the update
    ActivityLog::create([
        'organization_id' => $organization->id,
        'action' => 'updated',
        'changes' => $changes,
    ]);
});
```

**Event Properties:**

- `$event->organization` - The updated Organization instance
- `$event->changes` - Array of changed attributes (name, description, slug)

**When Dispatched:**

- When `UpdateOrganization` action completes
- Updated organization has fresh data loaded

**Use Cases:**

- Activity logging
- Send notification to members
- Update search indexes
- Trigger webhooks with changes
- Cache invalidation

### OrganizationDeleted

Dispatched when an organization is deleted (force deleted).

```php
use CleaniqueCoders\LaravelOrganization\Events\OrganizationDeleted;

Event::listen(OrganizationDeleted::class, function (OrganizationDeleted $event) {
    $organization = $event->organization;

    // Clean up related resources
    FileStorage::deleteDirectory("orgs/{$organization->uuid}");

    // Notify owner
    Notification::send($organization->owner, new OrganizationDeletedNotification());
});
```

**Event Properties:**

- `$event->organization` - The deleted Organization instance

**When Dispatched:**

- When `DeleteOrganization` action completes
- After permanent deletion (force delete)

**Use Cases:**

- Clean up external storage
- Send deletion notification
- Archive data
- Trigger cleanup webhooks

## Member Management Events

### MemberAdded

Dispatched when a user is added to an organization.

```php
use CleaniqueCoders\LaravelOrganization\Events\MemberAdded;

Event::listen(MemberAdded::class, function (MemberAdded $event) {
    $organization = $event->organization;
    $member = $event->member;
    $role = $event->role; // 'member' or 'administrator'

    // Send invitation/welcome email
    Mail::send(new AddedToOrganization($member, $organization, $role));
});
```

**Event Properties:**

- `$event->organization` - The Organization
- `$event->member` - The User being added
- `$event->role` - The role assigned (e.g., 'member', 'administrator')

**When Dispatched:**

- When `Organization::addUser()` is called
- From Livewire components or API requests
- When membership is created

**Use Cases:**

- Send welcome email
- Send notifications to other members
- Broadcast member join event
- Create audit log entry
- Send webhook

### MemberRemoved

Dispatched when a user is removed from an organization.

```php
use CleaniqueCoders\LaravelOrganization\Events\MemberRemoved;

Event::listen(MemberRemoved::class, function (MemberRemoved $event) {
    $organization = $event->organization;
    $member = $event->member;

    // Revoke API tokens for this org context
    $member->tokens()
        ->where('name', "like", "org-{$organization->id}-%")
        ->delete();

    // Notify remaining members
    Notification::send($organization->members, new MemberRemovedNotification($member));
});
```

**Event Properties:**

- `$event->organization` - The Organization
- `$event->member` - The User being removed

**When Dispatched:**

- When `Organization::removeUser()` is called
- From member management interface

**Use Cases:**

- Revoke access tokens
- Clean up permissions
- Notify remaining members
- Remove from shared resources
- Archive member access logs

### MemberRoleChanged

Dispatched when a member's role is updated (e.g., promoted to administrator).

```php
use CleaniqueCoders\LaravelOrganization\Events\MemberRoleChanged;

Event::listen(MemberRoleChanged::class, function (MemberRoleChanged $event) {
    $organization = $event->organization;
    $member = $event->member;
    $oldRole = $event->oldRole; // 'member'
    $newRole = $event->newRole; // 'administrator'

    // Notify member of role change
    Notification::send($member, new RoleChangedNotification(
        $organization,
        $oldRole,
        $newRole
    ));
});
```

**Event Properties:**

- `$event->organization` - The Organization
- `$event->member` - The User whose role changed
- `$event->oldRole` - Previous role value
- `$event->newRole` - New role value

**When Dispatched:**

- When `Organization::updateUserRole()` is called
- Only when role actually changes

**Use Cases:**

- Notify member of promotion/demotion
- Grant/revoke permissions
- Update audit trail
- Send webhook notification
- Broadcast role change

## Ownership Events

### OwnershipTransferred

Dispatched when organization ownership is transferred to a new owner.

```php
use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferred;

Event::listen(OwnershipTransferred::class, function (OwnershipTransferred $event) {
    $organization = $event->organization;
    $previousOwner = $event->previousOwner;
    $newOwner = $event->newOwner;

    // Notify both parties
    Notification::send($previousOwner, new OwnershipTransferredOut($organization, $newOwner));
    Notification::send($newOwner, new OwnershipTransferredIn($organization, $previousOwner));

    // Log the critical change
    AuditLog::logCriticalChange($organization, 'ownership_transferred', [
        'from' => $previousOwner->id,
        'to' => $newOwner->id,
    ]);
});
```

**Event Properties:**

- `$event->organization` - The Organization
- `$event->previousOwner` - The previous owner (User)
- `$event->newOwner` - The new owner (User)

**When Dispatched:**

- When `Organization::transferOwnership()` is called
- After ownership change is saved

**Use Cases:**

- Notify both old and new owner
- Log critical change
- Update permissions
- Send webhook notification
- Update accounting/billing records

## Creating Event Listeners

### Synchronous Listeners

Execute immediately when event is dispatched:

```php
<?php

namespace App\Listeners;

use CleaniqueCoders\LaravelOrganization\Events\OrganizationCreated;

class SendOrganizationCreatedNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrganizationCreated $event): void
    {
        $organization = $event->organization;

        // Send welcome email synchronously
        Mail::send(new WelcomeToOrganization($organization));
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    OrganizationCreated::class => [
        SendOrganizationCreatedNotification::class,
    ],
];
```

### Queued Listeners

Execute asynchronously using job queue:

```php
<?php

namespace App\Listeners;

use CleaniqueCoders\LaravelOrganization\Events\OrganizationDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class CleanupOrganizationData implements ShouldQueue
{
    /**
     * The number of times the queued listener may be attempted.
     */
    public $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(OrganizationDeleted $event): void
    {
        $organization = $event->organization;

        // This runs in the background queue
        FileStorage::deleteDirectory("orgs/{$organization->uuid}");
        CDN::purgeCache("org-{$organization->id}");
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    OrganizationDeleted::class => [
        CleanupOrganizationData::class, // Automatically queued
    ],
];
```

## Complete EventServiceProvider Example

```php
<?php

namespace App\Providers;

use CleaniqueCoders\LaravelOrganization\Events\MemberAdded;
use CleaniqueCoders\LaravelOrganization\Events\MemberRemoved;
use CleaniqueCoders\LaravelOrganization\Events\MemberRoleChanged;
use CleaniqueCoders\LaravelOrganization\Events\OrganizationCreated;
use CleaniqueCoders\LaravelOrganization\Events\OrganizationDeleted;
use CleaniqueCoders\LaravelOrganization\Events\OrganizationUpdated;
use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferred;
use App\Listeners\SendOrganizationCreatedNotification;
use App\Listeners\LogOrganizationUpdate;
use App\Listeners\CleanupOrganizationData;
use App\Listeners\NotifyMemberAdded;
use App\Listeners\CleanupMemberAccess;
use App\Listeners\NotifyMemberRoleChange;
use App\Listeners\NotifyOwnershipTransfer;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrganizationCreated::class => [
            SendOrganizationCreatedNotification::class,
        ],
        OrganizationUpdated::class => [
            LogOrganizationUpdate::class,
        ],
        OrganizationDeleted::class => [
            CleanupOrganizationData::class,
        ],
        MemberAdded::class => [
            NotifyMemberAdded::class,
        ],
        MemberRemoved::class => [
            CleanupMemberAccess::class,
        ],
        MemberRoleChanged::class => [
            NotifyMemberRoleChange::class,
        ],
        OwnershipTransferred::class => [
            NotifyOwnershipTransfer::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Custom event listeners can be registered here
    }
}
```

## Event Listener Examples

### Activity Logging Listener

```php
<?php

namespace App\Listeners;

use CleaniqueCoders\LaravelOrganization\Events\OrganizationUpdated;

class LogOrganizationUpdate
{
    public function handle(OrganizationUpdated $event): void
    {
        \App\Models\ActivityLog::create([
            'organization_id' => $event->organization->id,
            'user_id' => auth()->id(),
            'action' => 'organization_updated',
            'model_type' => 'Organization',
            'model_id' => $event->organization->id,
            'changes' => $event->changes,
            'ip_address' => request()->ip(),
        ]);
    }
}
```

### Email Notification Listener

```php
<?php

namespace App\Listeners;

use CleaniqueCoders\LaravelOrganization\Events\MemberAdded;
use App\Mail\MemberAddedToOrganization;
use Illuminate\Support\Facades\Mail;

class NotifyMemberAdded
{
    public function handle(MemberAdded $event): void
    {
        Mail::send(new MemberAddedToOrganization(
            $event->member,
            $event->organization,
            $event->role
        ));
    }
}
```

### Webhook Notification Listener

```php
<?php

namespace App\Listeners;

use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferred;
use Illuminate\Support\Facades\Http;

class SendOwnershipTransferredWebhook
{
    public function handle(OwnershipTransferred $event): void
    {
        $webhookUrl = $event->organization->getSetting('webhook_url');

        if (!$webhookUrl) {
            return;
        }

        Http::post($webhookUrl, [
            'event' => 'ownership_transferred',
            'organization_id' => $event->organization->id,
            'previous_owner_id' => $event->previousOwner->id,
            'new_owner_id' => $event->newOwner->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

### Real-time Broadcasting Listener

```php
<?php

namespace App\Listeners;

use CleaniqueCoders\LaravelOrganization\Events\MemberAdded;
use App\Events\MemberJoinedOrganization;

class BroadcastMemberJoined
{
    public function handle(MemberAdded $event): void
    {
        // Broadcast to all connected clients in the organization
        broadcast(new MemberJoinedOrganization(
            $event->organization,
            $event->member,
            $event->role
        ))->toOthers();
    }
}
```

## Testing Events

```php
<?php

use Illuminate\Support\Facades\Event;
use CleaniqueCoders\LaravelOrganization\Events\OrganizationCreated;
use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;

it('dispatches OrganizationCreated event', function () {
    Event::fake();

    $user = UserFactory::new()->create();
    $action = new CreateNewOrganization();

    $organization = $action->handle($user);

    Event::assertDispatched(OrganizationCreated::class, function (OrganizationCreated $event) use ($organization) {
        return $event->organization->is($organization);
    });
});

it('dispatches OrganizationUpdated with changes', function () {
    Event::fake();

    $organization = OrganizationFactory::new()->create();
    $organization->update(['name' => 'New Name']);

    Event::assertDispatched(OrganizationUpdated::class, function (OrganizationUpdated $event) {
        return isset($event->changes['name']);
    });
});
```

## Best Practices

1. **Keep Listeners Focused**: Each listener should do one thing well
2. **Use Queued Listeners**: For time-consuming operations (email, API calls)
3. **Handle Failures Gracefully**: Implement retry logic for queued listeners
4. **Test Event Dispatching**: Always test that events are dispatched correctly
5. **Document Your Listeners**: Explain what each listener does
6. **Use Events for Extensibility**: Let listeners handle custom business logic
7. **Monitor Event Performance**: Profile listeners in production
8. **Error Handling**: Wrap long-running operations in try-catch blocks

## Event Serialization

All events use Laravel's `SerializesModels` trait, ensuring they can be safely queued:

- Organization and User models are automatically serialized/deserialized
- Events can be dispatched to queued listeners
- Works with Redis, SQS, and other queue drivers

## Performance Considerations

- **Synchronous Listeners**: Execute immediately, blocking the request
- **Queued Listeners**: Execute asynchronously, recommended for heavy operations
- **Event Listeners Count**: Minimize the number of listeners per event
- **Cache Results**: Cache frequently accessed data within listeners
- **Batch Operations**: Use chunking for bulk operations in listeners

## Troubleshooting

### Events Not Firing

1. Check that listeners are registered in `EventServiceProvider`
2. Verify event is being dispatched (add logging)
3. Ensure listeners are not being skipped

### Listener Failing

1. Check queue worker is running: `php artisan queue:work`
2. Review queue job logs
3. Check listener exception handling
4. Verify database migrations are up to date

### Performance Issues

1. Move heavy operations to queued listeners
2. Profile listener execution time
3. Consider using event debouncing for duplicate events
4. Monitor queue size and worker count

For more information on Laravel events, visit the [official documentation](https://laravel.com/docs/events).
