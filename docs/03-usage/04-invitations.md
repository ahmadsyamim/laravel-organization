# Organization Invitations

The Organization Invitations system allows organizations to invite users to join via email. The system provides a complete workflow for sending, accepting, declining, and managing invitations with expiration tracking, role assignment, and event-driven architecture.

## Table of Contents

- [Overview](#overview)
- [Model](#model)
- [Actions](#actions)
- [Events](#events)
- [Livewire Component](#livewire-component)
- [Usage Examples](#usage-examples)
- [Testing](#testing)
- [API Reference](#api-reference)
- [Customization](#customization)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## Overview

Provides email invitations, token security, expiration, role assignment, events, Livewire UI, and resend capability.

## Authorization

### SendInvitation

The inviter must be either the organization owner or an active member of the organization. This security check prevents unauthorized users from sending invitations:

```php
// Will throw InvalidArgumentException if user lacks permission
$invitation = (new SendInvitation())->handle(
    organization: $organization,
    invitedBy: auth()->user(), // Must be owner or active member
    email: 'john@example.com',
    role: OrganizationRole::MEMBER
);
```

### ResendInvitation

Requires an authenticated user with permission to manage the organization:

```php
// User parameter is required for authorization check
$result = (new ResendInvitation)->handle(
    invitation: $invitation,
    user: auth()->user() // Required - must be owner or active member
);
```

### AcceptInvitation

Uses database transactions to ensure atomic operations when accepting invitations and adding users to organizations:

```php
// Wrapped in DB::transaction for data consistency
$organization = (new AcceptInvitation)->handle($invitation, $user);
```

## Model

`Invitation` model stores invitation state and lifecycle timestamps. Key helpers:

```php
$invitation->isPending();
$invitation->isAccepted();
$invitation->isDeclined();
$invitation->isExpired();
$invitation->isValid();
$invitation->accept($user);
$invitation->decline();
$invitation->getRoleEnum();
```

Factory states: `pending`, `accepted`, `declined`, `expired`, `admin`, `member`.

## Actions

### Sending Invitations

```php
$invitation = (new SendInvitation())->handle(
    organization: $organization,
    invitedBy: auth()->user(),
    email: 'john@example.com',
    role: OrganizationRole::MEMBER,
    expirationDays: 7
);
```

Validates email, uniqueness, pending state. Dispatches `InvitationSent`.

### Accepting Invitations

Adds user to org and marks accepted. Dispatches `InvitationAccepted` + `MemberAdded`.

### Declining Invitations

Marks declined. Dispatches `InvitationDeclined`.

### Resending Invitations

Regenerates token & expiration. Dispatches `InvitationSent`.

## Events

- `InvitationSent`
- `InvitationAccepted`
- `InvitationDeclined`

Listeners can send emails, log activity, trigger webhooks.

## Livewire Component

![Invitation Management](../../screenshots/invitation.png)

## Usage Examples

Programmatic send, accept via controller, event listening for welcome emails.

## Testing

Use action classes with `Event::fake()` to assert lifecycle events. Example factory usage for each state.

## API Reference

```php
// SendInvitation - invitedBy must be owner or active member
SendInvitation::handle(
    Organization $organization,
    User $invitedBy,
    string $email,
    OrganizationRole $role = OrganizationRole::MEMBER,
    int $expirationDays = 7
): Invitation

// AcceptInvitation - wrapped in database transaction
AcceptInvitation::handle(
    Invitation $invitation,
    User $user
): Organization

// DeclineInvitation
DeclineInvitation::handle(
    Invitation $invitation
): Invitation

// ResendInvitation - user parameter required for authorization
ResendInvitation::handle(
    Invitation $invitation,
    User $user,
    int $expirationDays = 7
): Invitation
```

## Customization

Extend actions to add custom validation (e.g. domain restriction). Publish and edit views for design tweaks.

## Best Practices

1. Validate emails strictly
2. Authorization is enforced automatically - only owners/members can send invitations
3. Use reasonable expiration (7–14 days)
4. Log all invitation lifecycle events
5. Send onboarding after acceptance
6. Periodically clean expired invites
7. AcceptInvitation is automatically wrapped in transactions for consistency
8. Always pass the authenticated user to ResendInvitation for authorization

## Troubleshooting

- Not expiring: check `isExpired()` logic and timestamps
- Events missing: ensure `EventServiceProvider` registration
- Duplicate membership errors: verify user not already added

## Integration Notes

Links: [Authorization & Policies](./02-authorization-and-policies.md) | [Events](../01-architecture/03-events.md)

## Performance Considerations

Indexes on email/token, pagination for large lists, soft deletes for audit.

Last updated: 2026-01-01
