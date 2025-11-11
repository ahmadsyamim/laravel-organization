# Organization Invitations

The Organization Invitations system allows organizations to invite users to join via email. The system provides a complete workflow for sending, accepting, declining, and managing invitations with expiration tracking, role assignment, and event-driven architecture.

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Model](#model)
- [Actions](#actions)
- [Events](#events)
- [Livewire Component](#livewire-component)
- [Usage Examples](#usage-examples)
- [Testing](#testing)
- [API Reference](#api-reference)
- [Customization](#customization)

## Overview

The invitation system provides:

- **Email-based invitations**: Send invitations to any email address
- **Token-based security**: Unique tokens for each invitation
- **Expiration tracking**: Automatic expiration after configurable days
- **Role assignment**: Assign roles (Member, Administrator) at invitation time
- **Event system**: Events fired on invitation lifecycle (sent, accepted, declined)
- **Livewire integration**: Built-in component for managing invitations
- **State management**: Track pending, accepted, and declined invitations
- **Resend capability**: Regenerate tokens for expired or unopened invitations

## Installation

After installing the laravel-organization package, publish the migration:

```bash
php artisan migrate
```

This creates the `invitations` table which stores all invitation data.

## Model

The `Invitation` model represents an invitation sent to a user to join an organization.

### Properties

```php
/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $invited_by_user_id
 * @property int|null $user_id
 * @property string $email
 * @property string $token
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
```

### Relationships

```php
// Get the organization this invitation belongs to
$invitation->organization(); // BelongsTo Organization

// Get the user who was invited (if they have an account)
$invitation->invitedUser(); // BelongsTo User

// Get the user who sent the invitation
$invitation->invitedByUser(); // BelongsTo User
```

### Methods

```php
// Check if invitation is pending (not accepted or declined)
$invitation->isPending(): bool

// Check if invitation has been accepted
$invitation->isAccepted(): bool

// Check if invitation has been declined
$invitation->isDeclined(): bool

// Check if invitation has expired
$invitation->isExpired(): bool

// Check if invitation is still valid (pending and not expired)
$invitation->isValid(): bool

// Accept the invitation
$invitation->accept(User $user): self

// Decline the invitation
$invitation->decline(): self

// Get the role as an enum
$invitation->getRoleEnum(): OrganizationRole
```

### Creating Invitations in Tests

```php
use CleaniqueCoders\LaravelOrganization\Models\Invitation;

// Basic invitation
$invitation = Invitation::factory()->create();

// Pending invitation
$invitation = Invitation::factory()->pending()->create();

// Accepted invitation
$invitation = Invitation::factory()->accepted()->create();

// Declined invitation
$invitation = Invitation::factory()->declined()->create();

// Expired invitation
$invitation = Invitation::factory()->expired()->create();

// Admin role
$invitation = Invitation::factory()->admin()->create();

// Member role
$invitation = Invitation::factory()->member()->create();
```

## Actions

Actions encapsulate the business logic for managing invitations.

### SendInvitation

Send an invitation to join an organization.

```php
use CleaniqueCoders\LaravelOrganization\Actions\SendInvitation;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

$action = new SendInvitation();

$invitation = $action->handle(
    organization: $organization,
    invitedBy: auth()->user(),
    email: 'john@example.com',
    role: OrganizationRole::MEMBER,    // Optional, defaults to MEMBER
    expirationDays: 7                  // Optional, defaults to 7
);
```

**Validation:**

- Email must be valid format
- User with that email cannot already be a member
- No active pending invitation should exist for that email

**Events:** Dispatches `InvitationSent` event

**Throws:** `InvalidArgumentException` for validation failures

### AcceptInvitation

Accept an invitation and add the user to the organization.

```php
use CleaniqueCoders\LaravelOrganization\Actions\AcceptInvitation;

$action = new AcceptInvitation();

$organization = $action->handle(
    invitation: $invitation,
    user: auth()->user()
);
```

**Validation:**

- Invitation must be pending (not already accepted or declined)
- Invitation must not be expired
- User email must match invitation email
- User must not already be a member

**Side effects:**

- Sets `accepted_at` timestamp
- Adds user to organization with invitation role
- Dispatches `InvitationAccepted` event
- Dispatches `MemberAdded` event (from organization)

**Throws:** `InvalidArgumentException` for validation failures

### DeclineInvitation

Decline an invitation.

```php
use CleaniqueCoders\LaravelOrganization\Actions\DeclineInvitation;

$action = new DeclineInvitation();

$invitation = $action->handle($invitation);
```

**Validation:**

- Invitation must be pending
- Invitation must not be expired

**Side effects:**

- Sets `declined_at` timestamp
- Dispatches `InvitationDeclined` event

**Throws:** `InvalidArgumentException` for validation failures

### ResendInvitation

Resend an invitation with a new token and expiration. This regenerates the token and resets the expiration date, allowing recipients to access the invitation again.

```php
use CleaniqueCoders\LaravelOrganization\Actions\ResendInvitation;

$action = new ResendInvitation();

$invitation = $action->handle(
    invitation: $invitation,
    expirationDays: 7  // Optional, defaults to 7
);
```

**Validation:**

- Invitation must be pending (not accepted or declined)

**Side effects:**

- Generates new random token
- Updates expiration date
- Dispatches `InvitationSent` event (for email resend and notifications)

**Throws:** `InvalidArgumentException` for validation failures

## Events

The invitation system dispatches events at key lifecycle points, allowing you to react to invitation changes.

### InvitationSent

Dispatched when an invitation is created and sent.

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;

Event::listen(InvitationSent::class, function (InvitationSent $event) {
    $invitation = $event->invitation;
    // Send email, log activity, trigger webhook, etc.
});
```

### InvitationAccepted

Dispatched when an invitation is accepted.

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationAccepted;

Event::listen(InvitationAccepted::class, function (InvitationAccepted $event) {
    $invitation = $event->invitation;
    // Log activity, send notifications, update records, etc.
});
```

### InvitationDeclined

Dispatched when an invitation is declined.

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationDeclined;

Event::listen(InvitationDeclined::class, function (InvitationDeclined $event) {
    $invitation = $event->invitation;
    // Log activity, notify organization owner, etc.
});
```

### Event Listeners

Example event listeners for common scenarios:

#### Email Notification Listener

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use App\Mail\InvitationMail;
use Illuminate\Support\Facades\Mail;

class SendInvitationEmail
{
    public function handle(InvitationSent $event)
    {
        Mail::to($event->invitation->email)->send(
            new InvitationMail($event->invitation)
        );
    }
}

// Register in EventServiceProvider
protected $listen = [
    InvitationSent::class => [
        SendInvitationEmail::class,
    ],
];
```

#### Activity Logging Listener

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use CleaniqueCoders\LaravelOrganization\Events\InvitationAccepted;
use CleaniqueCoders\LaravelOrganization\Events\InvitationDeclined;

class LogInvitationActivity
{
    public function handleSent(InvitationSent $event)
    {
        \Illuminate\Support\Facades\Log::info('Invitation sent', [
            'organization_id' => $event->invitation->organization_id,
            'email' => $event->invitation->email,
            'role' => $event->invitation->role,
        ]);
    }

    public function handleAccepted(InvitationAccepted $event)
    {
        \Illuminate\Support\Facades\Log::info('Invitation accepted', [
            'organization_id' => $event->invitation->organization_id,
            'user_id' => $event->invitation->user_id,
        ]);
    }

    public function handleDeclined(InvitationDeclined $event)
    {
        \Illuminate\Support\Facades\Log::info('Invitation declined', [
            'organization_id' => $event->invitation->organization_id,
            'email' => $event->invitation->email,
        ]);
    }
}

// Register in EventServiceProvider
protected $listen = [
    InvitationSent::class => [LogInvitationActivity::class],
    InvitationAccepted::class => [LogInvitationActivity::class],
    InvitationDeclined::class => [LogInvitationActivity::class],
];
```

#### Webhook Listener

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use Illuminate\Support\Facades\Http;

class TriggerWebhooks
{
    public function handle(InvitationSent $event)
    {
        Http::post(config('webhooks.invitation_url'), [
            'event' => 'invitation.sent',
            'invitation' => [
                'id' => $event->invitation->id,
                'email' => $event->invitation->email,
                'organization_id' => $event->invitation->organization_id,
            ],
        ]);
    }
}
```

## Livewire Component

The `InvitationManager` Livewire component provides a complete UI for managing organization invitations.

### Usage

```blade
<livewire:org::invitation-manager :organization="$organization" />
```

### Features

- **Send invitations**: Form to send new invitations with role selection
- **Manage pending invitations**: List all pending invitations with resend capability
- **User invitations**: Display invitations for the current user with accept/decline buttons
- **Pagination**: Paginated lists for large invitation sets
- **Real-time updates**: Livewire reactivity for instant feedback
- **Dark mode support**: Tailwind CSS with dark mode classes
- **Email notifications**: Integrates with email system for invitation delivery and resend

### Component Properties

```php
public Organization $organization;      // The organization context
public User $currentUser;               // Current authenticated user
public string $email = '';              // Form input for email
public string $role = 'member';         // Form input for role selection
public bool $showSendForm = false;      // Toggle send form visibility
```

### Component Methods

```php
// Send a new invitation
public function sendInvitation(): void

// Resend an existing invitation (regenerates token, updates expiration)
public function resendInvitation(int $invitationId): void

// Accept an invitation for the current user
public function acceptInvitation(int $invitationId): void

// Decline an invitation
public function declineInvitation(int $invitationId): void
```

### Component Events

The component dispatches Livewire events for notifications:

```php
// Success notification
$this->dispatch('notification', [
    'type' => 'success',
    'message' => 'Invitation sent to john@example.com',
]);

// Error notification
$this->dispatch('notification', [
    'type' => 'error',
    'message' => 'An error occurred',
]);
```

Listen to these events in your view:

```blade
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('notification', (data) => {
            const message = Array.isArray(data) && data.length > 0
                ? data[0]?.message || 'Action completed'
                : data?.message || 'Action completed';
            const type = Array.isArray(data) && data.length > 0
                ? data[0]?.type || 'success'
                : data?.type || 'success';
            showNotification(message, type);
        });
    });
</script>
```

### Component View Customization

Publish and customize the view:

```bash
php artisan vendor:publish --tag=laravel-organization-views
```

Then edit: `resources/views/vendor/laravel-organization/livewire/invitation-manager.blade.php`

## Usage Examples

### Example 1: Send an Invitation Programmatically

```php
use CleaniqueCoders\LaravelOrganization\Actions\SendInvitation;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;

// Get the organization and current user
$organization = Organization::find(1);
$inviter = auth()->user();

// Send the invitation
try {
    $invitation = (new SendInvitation())->handle(
        $organization,
        $inviter,
        'newmember@example.com',
        OrganizationRole::MEMBER,
        expirationDays: 14  // Invite expires in 14 days
    );

    \Log::info("Invitation sent to {$invitation->email}");
} catch (\InvalidArgumentException $e) {
    \Log::error("Failed to send invitation: {$e->getMessage()}");
}
```

### Example 2: Create an Invitation Route

```php
use CleaniqueCoders\LaravelOrganization\Actions\SendInvitation;
use Illuminate\Routing\Controller;

class InvitationController extends Controller
{
    public function store(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:member,administrator',
        ]);

        try {
            $invitation = (new SendInvitation())->handle(
                $organization,
                auth()->user(),
                $validated['email'],
                OrganizationRole::from($validated['role'])
            );

            return redirect()->back()->with('success', 'Invitation sent!');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['email' => $e->getMessage()]);
        }
    }
}
```

### Example 3: Accept Invitation Route

```php
use CleaniqueCoders\LaravelOrganization\Actions\AcceptInvitation;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;

class InvitationController extends Controller
{
    public function accept(Invitation $invitation)
    {
        // Verify the token in URL matches (implementation detail)
        if (request()->query('token') !== $invitation->token) {
            abort(401);
        }

        try {
            $organization = (new AcceptInvitation())->handle(
                $invitation,
                auth()->user()
            );

            return redirect()->route('organizations.show', $organization)
                ->with('success', "You've joined {$organization->name}!");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
```

### Example 4: Listen to Invitation Events

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationAccepted;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvitationAccepted::class => [
            SendWelcomeEmail::class,
            LogMemberJoined::class,
        ],
    ];
}

class SendWelcomeEmail
{
    public function handle(InvitationAccepted $event)
    {
        $user = $event->invitation->invitedUser;
        $organization = $event->invitation->organization;

        Mail::to($user->email)->queue(
            new WelcomeMail($user, $organization)
        );
    }
}
```

## Testing

The package includes comprehensive tests for the invitation system, covering all actions and state transitions.

### Running Tests

```bash
composer test
```

### Writing Tests

```php
use CleaniqueCoders\LaravelOrganization\Actions\SendInvitation;
use CleaniqueCoders\LaravelOrganization\Actions\ResendInvitation;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use Illuminate\Support\Facades\Event;

describe('Sending Invitations', function () {
    it('can send an invitation', function () {
        Event::fake();

        $organization = Organization::factory()->create();
        $inviter = User::factory()->create();

        $invitation = (new SendInvitation())->handle(
            $organization,
            $inviter,
            'john@example.com'
        );

        expect($invitation)->toBeInstanceOf(Invitation::class)
            ->and($invitation->isPending())->toBeTrue();

        Event::assertDispatched(InvitationSent::class);
    });

    it('throws exception for invalid email', function () {
        $organization = Organization::factory()->create();
        $inviter = User::factory()->create();

        (new SendInvitation())->handle(
            $organization,
            $inviter,
            'invalid-email'
        );
    })->throws(\InvalidArgumentException::class);

    it('can resend an invitation with new token', function () {
        Event::fake();

        $invitation = Invitation::factory()->pending()->create();
        $oldToken = $invitation->token;

        $resent = (new ResendInvitation())->handle($invitation);

        expect($resent->token)->not->toBe($oldToken)
            ->and($resent->isPending())->toBeTrue();

        Event::assertDispatched(InvitationSent::class);
    });
});
```

## API Reference

### SendInvitation::handle()

```php
public function handle(
    Organization $organization,
    User $invitedBy,
    string $email,
    OrganizationRole $role = OrganizationRole::MEMBER,
    int $expirationDays = 7
): Invitation
```

### AcceptInvitation::handle()

```php
public function handle(
    Invitation $invitation,
    User $user
): Organization
```

### DeclineInvitation::handle()

```php
public function handle(
    Invitation $invitation
): Invitation
```

### ResendInvitation::handle()

```php
public function handle(
    Invitation $invitation,
    int $expirationDays = 7
): Invitation
```

### Invitation Model Methods

```php
// Query methods
public function organization(): BelongsTo
public function invitedUser(): BelongsTo
public function invitedByUser(): BelongsTo

// State checking
public function isPending(): bool
public function isAccepted(): bool
public function isDeclined(): bool
public function isExpired(): bool
public function isValid(): bool

// State modification
public function accept(User $user): self
public function decline(): self
public function getRoleEnum(): OrganizationRole
```

## Customization

### Custom Expiration Duration

Override the default expiration in your config:

```php
// config/organization.php
return [
    'invitations' => [
        'default_expiration_days' => 14,  // Changed from 7
    ],
];
```

Then use in SendInvitation:

```php
$invitation = (new SendInvitation())->handle(
    $organization,
    $inviter,
    'email@example.com',
    expirationDays: config('organization.invitations.default_expiration_days')
);
```

### Custom Validation Rules

Extend the actions with custom validation:

```php
class CustomSendInvitation extends SendInvitation
{
    public function handle(
        Organization $organization,
        User $invitedBy,
        string $email,
        OrganizationRole $role = OrganizationRole::MEMBER,
        int $expirationDays = 7
    ): Invitation {
        // Add custom validation
        if (!str_ends_with($email, '@company.com')) {
            throw new \InvalidArgumentException('Only company emails allowed');
        }

        return parent::handle(
            $organization,
            $invitedBy,
            $email,
            $role,
            $expirationDays
        );
    }
}
```

### Custom Event Listeners

Listen to events and perform custom actions:

```php
// app/Listeners/InvitationListener.php
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;

class InvitationListener
{
    public function handle(InvitationSent $event)
    {
        // Custom logic: send SMS, sync with external service, etc.
        $this->sendCustomNotification($event->invitation);
    }

    private function sendCustomNotification(Invitation $invitation)
    {
        // Implementation
    }
}

// app/Providers/EventServiceProvider.php
protected $listen = [
    InvitationSent::class => [
        InvitationListener::class,
    ],
];
```

### Custom Blade View

Publish and customize the component view:

```bash
php artisan vendor:publish --tag=laravel-organization-views
```

Then customize `resources/views/vendor/laravel-organization/livewire/invitation-manager.blade.php` to match your design system.

## Best Practices

1. **Always validate emails** before sending invitations
2. **Use role-based permissions** to control who can send invitations
3. **Set appropriate expiration times** (7-14 days is recommended)
4. **Monitor invitation acceptance rates** to optimize outreach
5. **Send welcome emails** when invitations are accepted
6. **Track invitation sources** for analytics
7. **Clean up expired invitations** periodically with scheduled tasks
8. **Use transactions** when accepting invitations to ensure consistency
9. **Log all invitation activities** for audit trails
10. **Test invitation workflows** thoroughly in staging

## Troubleshooting

### Invitations not expiring

Ensure you're checking `isExpired()` or `isValid()` methods, not relying on database queries alone. The expiration is checked at runtime.

### Events not firing

Verify event listeners are registered in `EventServiceProvider` and the queue driver is configured if using queued listeners.

### Email not matching

The system normalizes emails to lowercase. Ensure comparison is case-insensitive or use the `isValid()` method.

### User already a member

The system prevents duplicate memberships. Check if the email already exists in the organization before inviting.

## Integration with Other Features

### With Authorization Policies

```php
// Check if user can manage invitations
if (auth()->user()->can('manageMembers', $organization)) {
    // Send invitation
}
```

### With Activity Logging

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationAccepted;

class LogInvitationActivity
{
    public function handle(InvitationAccepted $event)
    {
        activity()
            ->on($event->invitation->organization)
            ->withProperties(['invitation_id' => $event->invitation->id])
            ->log('member_joined');
    }
}
```

### With Notifications

```php
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;

class NotifyInvitationSent
{
    public function handle(InvitationSent $event)
    {
        Notification::route('mail', $event->invitation->email)
            ->notify(new SendInvitationNotification($event->invitation));
    }
}
```

## Performance Considerations

- **Index on email**: Queries use indexed lookups for fast invitation retrieval
- **Pagination**: Use pagination for large invitation lists
- **Composite indexes**: Efficiently query pending invitations by organization
- **Soft deletes**: Deleted invitations are retained for audit trails
- **Token lookup**: Tokens are unique indexed for secure lookup

---

**Last Updated**: 2025-11-11

For more information, see [Authorization](./authorization.md) and [Events](./events.md) documentation.
