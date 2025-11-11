<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\InvitationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Livewire\InvitationManager;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    config(['cache.default' => 'array']); // Use array cache for testing
    $this->user = UserFactory::new()->create();
    $this->organization = OrganizationFactory::new()->ownedBy($this->user)->create();
    Auth::login($this->user);
});

afterEach(function () {
    Auth::logout();
});

describe('InvitationManager Livewire Component Mounting', function () {
    it('mounts with organization', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertSet('organization.id', $this->organization->id);
    });

    it('sets current user on mount', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertSet('currentUser.id', $this->user->id);
    });

    it('initializes role to member', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertSet('role', 'member');
    });

    it('initializes empty email', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertSet('email', '');
    });

    it('initializes showSendForm to false', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertSet('showSendForm', false);
    });
});

describe('InvitationManager Livewire Component Send Invitation', function () {
    it('can send invitation with valid email', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'newuser@example.com')
            ->set('role', 'member')
            ->call('sendInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invitations', [
            'organization_id' => $this->organization->id,
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);
    });

    it('can send invitation with administrator role', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'admin@example.com')
            ->set('role', 'administrator')
            ->call('sendInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('invitations', [
            'organization_id' => $this->organization->id,
            'email' => 'admin@example.com',
            'role' => 'administrator',
        ]);
    });

    it('validates required email', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', '')
            ->call('sendInvitation')
            ->assertHasErrors(['email' => 'required']);
    });

    it('validates email format', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'invalid-email')
            ->call('sendInvitation')
            ->assertHasErrors(['email' => 'email']);
    });

    it('validates maximum email length', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', str_repeat('a', 250).'@example.com')
            ->call('sendInvitation')
            ->assertHasErrors(['email' => 'max']);
    });

    it('validates required role', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'test@example.com')
            ->set('role', '')
            ->call('sendInvitation')
            ->assertHasErrors(['role' => 'required']);
    });

    it('validates role is valid value', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'test@example.com')
            ->set('role', 'invalid-role')
            ->call('sendInvitation')
            ->assertHasErrors(['role' => 'in']);
    });

    it('dispatches success notification after sending', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'newuser@example.com')
            ->set('role', 'member')
            ->call('sendInvitation')
            ->assertDispatched('notification');
    });

    it('resets form after successful send', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'newuser@example.com')
            ->set('role', 'administrator')
            ->set('showSendForm', true)
            ->call('sendInvitation')
            ->assertSet('email', '')
            ->assertSet('showSendForm', false);
    });

    it('shows error for duplicate invitation', function () {
        // Create an existing invitation
        InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'existing@example.com']);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->set('email', 'existing@example.com')
            ->set('role', 'member')
            ->call('sendInvitation')
            ->assertHasErrors('email');
    });
});

describe('InvitationManager Livewire Component Resend Invitation', function () {
    it('can resend pending invitation', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'test@example.com']);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('resendInvitation', $invitation)
            ->assertDispatched('notification');
    });

    it('dispatches success notification after resending', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'test@example.com']);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('resendInvitation', $invitation)
            ->assertDispatched('notification');
    });

    it('shows error when resending accepted invitation', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->accepted()
            ->create(['email' => 'test@example.com']);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('resendInvitation', $invitation)
            ->assertDispatched('notification');
    });
});

describe('InvitationManager Livewire Component Accept Invitation', function () {
    it('can accept pending invitation', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => $this->user->email]);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('acceptInvitation', $invitation)
            ->assertDispatched('notification');

        $invitation->refresh();
        expect($invitation->accepted_at)->not->toBeNull();
    });

    it('dispatches success notification after accepting', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => $this->user->email]);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('acceptInvitation', $invitation)
            ->assertDispatched('notification');
    });

    it('adds user to organization after accepting', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => $this->user->email]);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('acceptInvitation', $invitation);

        expect($this->organization->users()->where('user_id', $this->user->id)->exists())->toBeTrue();
    });

    it('shows error when accepting already accepted invitation', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->accepted()
            ->create(['email' => $this->user->email]);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('acceptInvitation', $invitation)
            ->assertDispatched('notification');
    });
});

describe('InvitationManager Livewire Component Decline Invitation', function () {
    it('can decline pending invitation', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => $this->user->email]);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('declineInvitation', $invitation)
            ->assertDispatched('notification');

        $invitation->refresh();
        expect($invitation->declined_at)->not->toBeNull();
    });

    it('dispatches success notification after declining', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => $this->user->email]);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('declineInvitation', $invitation)
            ->assertDispatched('notification');
    });

    it('shows error when declining already declined invitation', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->declined()
            ->create(['email' => $this->user->email]);

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->call('declineInvitation', $invitation)
            ->assertDispatched('notification');
    });
});

describe('InvitationManager Livewire Component Pending Invitations', function () {
    it('displays pending invitations for organization', function () {
        InvitationFactory::new()
            ->for($this->organization)
            ->count(3)
            ->create();

        $component = Livewire::test(InvitationManager::class, ['organization' => $this->organization]);

        expect($component->get('pendingInvitations')->count())->toBe(3);
    });

    it('excludes accepted invitations from pending list', function () {
        InvitationFactory::new()
            ->for($this->organization)
            ->create();

        InvitationFactory::new()
            ->for($this->organization)
            ->accepted()
            ->create();

        $component = Livewire::test(InvitationManager::class, ['organization' => $this->organization]);

        expect($component->get('pendingInvitations')->count())->toBe(1);
    });

    it('excludes declined invitations from pending list', function () {
        InvitationFactory::new()
            ->for($this->organization)
            ->create();

        InvitationFactory::new()
            ->for($this->organization)
            ->declined()
            ->create();

        $component = Livewire::test(InvitationManager::class, ['organization' => $this->organization]);

        expect($component->get('pendingInvitations')->count())->toBe(1);
    });

    it('excludes expired invitations from pending list', function () {
        InvitationFactory::new()
            ->for($this->organization)
            ->create();

        InvitationFactory::new()
            ->for($this->organization)
            ->expired()
            ->create();

        $component = Livewire::test(InvitationManager::class, ['organization' => $this->organization]);

        expect($component->get('pendingInvitations')->count())->toBe(1);
    });

    it('displays user pending invitations', function () {
        InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => $this->user->email]);

        $component = Livewire::test(InvitationManager::class, ['organization' => $this->organization]);

        expect($component->get('userPendingInvitations')->count())->toBe(1);
    });
});

describe('InvitationManager Livewire Component Render', function () {
    it('renders correct view', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertViewIs('org::livewire.invitation-manager');
    });

    it('passes pending invitations to view', function () {
        InvitationFactory::new()
            ->for($this->organization)
            ->create();

        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertViewHas('pendingInvitations');
    });

    it('passes user pending invitations to view', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertViewHas('userPendingInvitations');
    });

    it('passes roles to view', function () {
        Livewire::test(InvitationManager::class, ['organization' => $this->organization])
            ->assertViewHas('roles');
    });
});
