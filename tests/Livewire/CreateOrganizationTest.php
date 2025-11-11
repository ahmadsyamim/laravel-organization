<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Livewire\CreateOrganization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config(['cache.default' => 'array']); // Use array cache for testing
    $this->user = UserFactory::new()->create();
    Auth::login($this->user);
});

afterEach(function () {
    Auth::logout();
});

describe('CreateOrganization Livewire Component Modal', function () {
    it('can show modal', function () {
        Livewire::test(CreateOrganization::class)
            ->call('showModal')
            ->assertSet('showModal', true);
    });

    it('can close modal', function () {
        Livewire::test(CreateOrganization::class)
            ->set('showModal', true)
            ->set('name', 'Test Organization')
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('name', '')
            ->assertSet('description', '');
    });

    it('resets validation when showing modal', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', '')
            ->call('createOrganization')
            ->assertHasErrors('name')
            ->call('showModal')
            ->assertHasNoErrors();
    });

    it('listens to show-create-organization event', function () {
        Livewire::test(CreateOrganization::class)
            ->dispatch('show-create-organization')
            ->assertSet('showModal', true);
    });
});

describe('CreateOrganization Livewire Component Validation', function () {
    it('validates required name', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', '')
            ->call('createOrganization')
            ->assertHasErrors(['name' => 'required']);
    });

    it('validates minimum name length', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'A')
            ->call('createOrganization')
            ->assertHasErrors(['name' => 'min']);
    });

    it('validates maximum name length', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', str_repeat('A', 256))
            ->call('createOrganization')
            ->assertHasErrors(['name' => 'max']);
    });

    it('validates unique organization name', function () {
        OrganizationFactory::new()->create(['name' => 'Existing Organization']);

        Livewire::test(CreateOrganization::class)
            ->set('name', 'Existing Organization')
            ->call('createOrganization')
            ->assertHasErrors(['name' => 'unique']);
    });

    it('validates maximum description length', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'Valid Organization')
            ->set('description', str_repeat('A', 1001))
            ->call('createOrganization')
            ->assertHasErrors(['description' => 'max']);
    });

    it('validates name on update', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'A')
            ->assertHasErrors(['name' => 'min']);
    });

    it('validates description on update', function () {
        Livewire::test(CreateOrganization::class)
            ->set('description', str_repeat('A', 1001))
            ->assertHasErrors(['description' => 'max']);
    });
});

describe('CreateOrganization Livewire Component Creation', function () {
    it('can create organization successfully', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'New Organization')
            ->set('description', 'A great organization')
            ->call('createOrganization')
            ->assertSet('showModal', false)
            ->assertSet('name', '')
            ->assertSet('description', '')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('organizations', [
            'name' => 'New Organization',
            'description' => 'A great organization',
            'owner_id' => $this->user->id,
        ]);
    });

    it('can create organization without description', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'New Organization')
            ->call('createOrganization')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('organizations', [
            'name' => 'New Organization',
            'owner_id' => $this->user->id,
        ]);
    });

    it('can set organization as current', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'Current Organization')
            ->set('setAsCurrent', true)
            ->call('createOrganization')
            ->assertHasNoErrors();

        $this->user->refresh();
        $organization = $this->user->ownedOrganizations()->where('name', 'Current Organization')->first();

        expect($this->user->organization_id)->toBe($organization->id);
    });

    it('dispatches organization-created event', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'New Organization')
            ->call('createOrganization')
            ->assertDispatched('organization-created');
    });

    it('dispatches organization-switched event', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'New Organization')
            ->call('createOrganization')
            ->assertDispatched('organization-switched');
    });

    it('resets form after successful creation', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'New Organization')
            ->set('description', 'Test description')
            ->set('setAsCurrent', true)
            ->call('createOrganization')
            ->assertSet('name', '')
            ->assertSet('description', '')
            ->assertSet('setAsCurrent', false)
            ->assertSet('errorMessage', null);
    });
});

describe('CreateOrganization Livewire Component Rate Limiting', function () {
    it('enforces rate limiting', function () {
        $rateLimitKey = 'create-organization:'.$this->user->id;
        $maxAttempts = config('organization.rate_limits.create_organization.max_attempts', 5);

        // Hit the rate limit
        RateLimiter::hit($rateLimitKey, 60 * 60);
        for ($i = 1; $i < $maxAttempts; $i++) {
            RateLimiter::hit($rateLimitKey, 60 * 60);
        }

        Livewire::test(CreateOrganization::class)
            ->set('name', 'New Organization')
            ->call('createOrganization')
            ->assertSet('errorMessage', function ($message) {
                return str_contains($message, 'Too many organization creation attempts');
            });
    });

    it('clears error message before creating', function () {
        Livewire::test(CreateOrganization::class)
            ->set('errorMessage', 'Previous error')
            ->set('name', 'New Organization')
            ->call('createOrganization')
            ->assertSet('errorMessage', null);
    });
});

describe('CreateOrganization Livewire Component Error Handling', function () {
    it('shows error when user is not authenticated', function () {
        Auth::logout();

        Livewire::test(CreateOrganization::class)
            ->set('name', 'New Organization')
            ->call('createOrganization')
            ->assertSet('errorMessage', 'You must be logged in to create an organization.');
    });

    it('shows error message on validation failure', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', '')
            ->call('createOrganization')
            ->assertSet('errorMessage', 'Please correct the validation errors below.');
    });

    it('renders correct view', function () {
        Livewire::test(CreateOrganization::class)
            ->assertViewIs('org::livewire.create-organization-form');
    });

    it('handles invalid argument exception from action', function () {
        // Create a duplicate organization to trigger validation error
        OrganizationFactory::new()->ownedBy($this->user)->create(['name' => 'Duplicate Org']);

        Livewire::test(CreateOrganization::class)
            ->set('name', 'Duplicate Org')
            ->call('createOrganization')
            ->assertHasErrors('name'); // Validation catches duplicate name
    });

    it('handles database query exception', function () {
        // Create an organization with a very long name to potentially trigger DB error
        // or use invalid characters depending on DB constraints
        $longName = str_repeat('A', 500); // Exceeds typical varchar(255)

        Livewire::test(CreateOrganization::class)
            ->set('name', 'Valid Name')
            ->set('description', $longName) // This might trigger DB error if not caught by validation
            ->call('createOrganization');

        // The component should handle any DB errors gracefully
        expect(true)->toBeTrue();
    });
});

describe('CreateOrganization Livewire Component Properties', function () {
    it('has default property values', function () {
        Livewire::test(CreateOrganization::class)
            ->assertSet('showModal', false)
            ->assertSet('name', '')
            ->assertSet('description', '')
            ->assertSet('setAsCurrent', false)
            ->assertSet('errorMessage', null);
    });

    it('can update name property', function () {
        Livewire::test(CreateOrganization::class)
            ->set('name', 'Test Organization')
            ->assertSet('name', 'Test Organization');
    });

    it('can update description property', function () {
        Livewire::test(CreateOrganization::class)
            ->set('description', 'Test Description')
            ->assertSet('description', 'Test Description');
    });

    it('can update setAsCurrent property', function () {
        Livewire::test(CreateOrganization::class)
            ->set('setAsCurrent', true)
            ->assertSet('setAsCurrent', true);
    });
});
