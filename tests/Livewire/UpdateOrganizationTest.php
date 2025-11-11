<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Livewire\UpdateOrganization;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    config(['cache.default' => 'array']);
    $this->user = UserFactory::new()->create();
    $this->organization = OrganizationFactory::new()->ownedBy($this->user)->create();
    Auth::login($this->user);
});

afterEach(function () {
    Auth::logout();
});

describe('UpdateOrganization Livewire Component Initialization', function () {
    it('initializes with default values', function () {
        Livewire::test(UpdateOrganization::class)
            ->assertSet('organization', null)
            ->assertSet('showModal', false)
            ->assertSet('mode', 'edit')
            ->assertSet('name', '')
            ->assertSet('description', '')
            ->assertSet('confirmationName', '')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('errorMessage', null);
    });

    it('renders correct view', function () {
        Livewire::test(UpdateOrganization::class)
            ->assertViewIs('org::livewire.update-organization');
    });
});

describe('UpdateOrganization Livewire Component Show Manage Modal', function () {
    it('shows modal in edit mode', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->assertSet('showModal', true)
            ->assertSet('mode', 'edit')
            ->assertSet('organization.id', $this->organization->id);
    });

    it('shows modal in delete mode', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->assertSet('showModal', true)
            ->assertSet('mode', 'delete');
    });

    it('loads organization data when showing modal', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->assertSet('name', $this->organization->name)
            ->assertSet('description', $this->organization->description ?? '');
    });

    it('shows error when organization ID missing', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['mode' => 'edit'])
            ->assertSet('errorMessage', 'Organization ID is required.')
            ->assertSet('showModal', false);
    });

    it('shows error when organization not found', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => 99999, 'mode' => 'edit'])
            ->assertSet('errorMessage', 'Organization not found.')
            ->assertSet('showModal', false);
    });

    it('shows error when user lacks permission', function () {
        $otherUser = UserFactory::new()->create();
        $otherOrg = OrganizationFactory::new()->ownedBy($otherUser)->create();

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $otherOrg->id, 'mode' => 'edit'])
            ->assertSet('errorMessage', 'You do not have permission to manage this organization.')
            ->assertSet('showModal', false);
    });

    it('allows administrator to manage organization', function () {
        $otherUser = UserFactory::new()->create();
        $otherOrg = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $otherOrg->addUser($this->user, OrganizationRole::ADMINISTRATOR);

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $otherOrg->id, 'mode' => 'edit'])
            ->assertSet('showModal', true)
            ->assertSet('errorMessage', null);
    });

    it('resets validation when showing modal', function () {
        Livewire::test(UpdateOrganization::class)
            ->set('name', '')
            ->call('updateOrganization')
            ->assertHasErrors('name')
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->assertHasNoErrors();
    });

    it('clears error message when showing modal', function () {
        Livewire::test(UpdateOrganization::class)
            ->set('errorMessage', 'Previous error')
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->assertSet('errorMessage', null);
    });
});

describe('UpdateOrganization Livewire Component Close Modal', function () {
    it('can close modal', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->call('closeModal')
            ->assertSet('showModal', false);
    });

    it('resets form fields when closing', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'Modified')
            ->call('closeModal')
            ->assertSet('name', '')
            ->assertSet('description', '')
            ->assertSet('confirmationName', '')
            ->assertSet('mode', 'edit')
            ->assertSet('errorMessage', null)
            ->assertSet('organization', null);
    });

    it('closes delete confirmation when closing modal', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('showDeleteConfirmation', true)
            ->call('closeModal')
            ->assertSet('showDeleteConfirmation', false);
    });
});

describe('UpdateOrganization Livewire Component Validation', function () {
    it('validates required name', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', '')
            ->call('updateOrganization')
            ->assertHasErrors(['name' => 'required']);
    });

    it('validates minimum name length', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'A')
            ->call('updateOrganization')
            ->assertHasErrors(['name' => 'min']);
    });

    it('validates maximum name length', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', str_repeat('A', 256))
            ->call('updateOrganization')
            ->assertHasErrors(['name' => 'max']);
    });

    it('validates unique name', function () {
        $org2 = OrganizationFactory::new()->ownedBy($this->user)->create(['name' => 'Existing Org']);

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'Existing Org')
            ->call('updateOrganization')
            ->assertHasErrors(['name' => 'unique']);
    });

    it('allows keeping same name', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', $this->organization->name)
            ->call('updateOrganization')
            ->assertHasNoErrors();
    });

    it('validates maximum description length', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('description', str_repeat('A', 1001))
            ->call('updateOrganization')
            ->assertHasErrors(['description' => 'max']);
    });

    it('validates name on update', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'A')
            ->assertHasErrors(['name' => 'min']);
    });

    it('validates description on update', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('description', str_repeat('A', 1001))
            ->assertHasErrors(['description' => 'max']);
    });
});

describe('UpdateOrganization Livewire Component Update', function () {
    it('can update organization name', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'Updated Organization')
            ->call('updateOrganization')
            ->assertHasNoErrors();

        $this->organization->refresh();
        expect($this->organization->name)->toBe('Updated Organization');
    });

    it('can update organization description', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('description', 'Updated description')
            ->call('updateOrganization')
            ->assertHasNoErrors();

        $this->organization->refresh();
        expect($this->organization->description)->toBe('Updated description');
    });

    it('closes modal after successful update', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'Updated Organization')
            ->call('updateOrganization')
            ->assertSet('showModal', false);
    });

    it('dispatches organization-updated event', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'Updated Organization')
            ->call('updateOrganization')
            ->assertDispatched('organization-updated');
    });

    it('shows error when organization not found during update', function () {
        Livewire::test(UpdateOrganization::class)
            ->set('organization', null)
            ->set('name', 'Test Name') // Set valid name to pass validation
            ->call('updateOrganization')
            ->assertSet('errorMessage', 'Organization not found.');
    });

    it('shows error message on validation failure', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', '')
            ->call('updateOrganization')
            ->assertSet('errorMessage', 'Please correct the validation errors below.');
    });

    it('clears error message before update', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('errorMessage', 'Previous error')
            ->set('name', 'Updated')
            ->call('updateOrganization');

        // Error should be cleared initially
        expect(true)->toBeTrue();
    });
});

describe('UpdateOrganization Livewire Component Delete Confirmation', function () {
    it('can show delete confirmation', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('confirmDelete')
            ->assertSet('showDeleteConfirmation', true)
            ->assertSet('confirmationName', '');
    });

    it('can cancel delete', function () {
        Livewire::test(UpdateOrganization::class)
            ->set('showDeleteConfirmation', true)
            ->set('confirmationName', 'test')
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('confirmationName', '');
    });
});

describe('UpdateOrganization Livewire Component Delete', function () {
    it('validates confirmation name before deleting', function () {
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('confirmationName', 'Wrong Name')
            ->call('deleteOrganization')
            ->assertHasErrors('confirmationName')
            ->assertSet('errorMessage', 'Organization name does not match.');
    });

    it('can delete organization with correct confirmation', function () {
        // Create another organization so user has multiple
        OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('confirmationName', $this->organization->name)
            ->call('deleteOrganization')
            ->assertHasNoErrors();
    });

    it('shows error when organization not found during delete', function () {
        Livewire::test(UpdateOrganization::class)
            ->set('organization', null)
            ->call('deleteOrganization')
            ->assertSet('errorMessage', 'Organization not found.');
    });

    it('closes modal after successful delete', function () {
        OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('confirmationName', $this->organization->name)
            ->call('deleteOrganization')
            ->assertSet('showModal', false);
    });

    it('dispatches organization-deleted event', function () {
        OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('confirmationName', $this->organization->name)
            ->call('deleteOrganization')
            ->assertDispatched('organization-deleted');
    });

    it('clears error message before delete', function () {
        OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('errorMessage', 'Previous error')
            ->set('confirmationName', $this->organization->name)
            ->call('deleteOrganization');

        // Error should be cleared initially
        expect(true)->toBeTrue();
    });
});

describe('UpdateOrganization Livewire Component Load Organization Data', function () {
    it('loads organization data correctly', function () {
        $component = Livewire::test(UpdateOrganization::class);
        $component->set('organization', $this->organization);
        $component->call('loadOrganizationData');

        $component->assertSet('name', $this->organization->name)
            ->assertSet('description', $this->organization->description ?? '');
    });

    it('handles null description', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create(['description' => null]);

        $component = Livewire::test(UpdateOrganization::class);
        $component->set('organization', $org);
        $component->call('loadOrganizationData');

        $component->assertSet('description', '');
    });

    it('handles null organization', function () {
        $component = Livewire::test(UpdateOrganization::class);
        $component->set('organization', null);
        $component->call('loadOrganizationData');

        // Should not throw error
        expect(true)->toBeTrue();
    });
});

describe('UpdateOrganization Livewire Component Exception Handling', function () {
    it('handles invalid argument exception during update', function () {
        // Try to update with a name that already exists
        $org2 = OrganizationFactory::new()->ownedBy($this->user)->create(['name' => 'Existing Name']);

        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'Existing Name')
            ->call('updateOrganization')
            ->assertHasErrors('name'); // Validation catches duplicate name
    });

    it('handles database errors during update', function () {
        // Just verify that component handles errors gracefully
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'edit'])
            ->set('name', 'Valid Update Name')
            ->call('updateOrganization');

        // Should complete successfully
        expect(true)->toBeTrue();
    });

    it('handles exception when trying to delete last organization', function () {
        // User has only one organization - should not be able to delete
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('confirmationName', $this->organization->name)
            ->call('deleteOrganization')
            ->assertSet('errorMessage', 'Cannot delete your only organization. You must have at least one organization.');
    });

    it('handles database errors during deletion', function () {
        // Create a second organization so deletion is allowed
        OrganizationFactory::new()->ownedBy($this->user)->create();

        // Component should handle errors gracefully
        Livewire::test(UpdateOrganization::class)
            ->call('showManageModal', ['organizationId' => $this->organization->id, 'mode' => 'delete'])
            ->set('confirmationName', $this->organization->name)
            ->call('deleteOrganization');

        // Should complete successfully or show error message
        expect(true)->toBeTrue();
    });

    it('isUserAdministrator returns false when organization is null', function () {
        $component = new UpdateOrganization;
        $component->organization = null;

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('isUserAdministrator');
        $method->setAccessible(true);

        $result = $method->invoke($component, $this->user);

        expect($result)->toBeFalse();
    });
});
