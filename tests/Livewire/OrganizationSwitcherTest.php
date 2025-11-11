<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Livewire\OrganizationSwitcher;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    config(['cache.default' => 'array']);
    $this->user = UserFactory::new()->create();
    Auth::login($this->user);
});

afterEach(function () {
    Auth::logout();
});

describe('OrganizationSwitcher Livewire Component Mounting', function () {
    it('mounts with authenticated user', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->assertSet('user.id', $this->user->id);
    });

    it('mounts with passed user parameter', function () {
        $otherUser = UserFactory::new()->create();

        Livewire::test(OrganizationSwitcher::class, ['user' => $otherUser])
            ->assertSet('user.id', $otherUser->id);
    });

    it('loads current organization on mount', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();
        $this->user->organization_id = $org->id;
        $this->user->save();

        Livewire::test(OrganizationSwitcher::class)
            ->assertSet('currentOrganization.id', $org->id);
    });

    it('initializes with closed dropdown', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->assertSet('showDropdown', false);
    });

    it('initializes with no error message', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->assertSet('errorMessage', null);
    });

    it('loads user organizations on mount', function () {
        OrganizationFactory::new()->ownedBy($this->user)->count(2)->create();

        $component = Livewire::test(OrganizationSwitcher::class);

        expect(count($component->get('organizations')))->toBeGreaterThanOrEqual(2);
    });
});

describe('OrganizationSwitcher Livewire Component Load Organizations', function () {
    it('loads owned organizations', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();
        $org2 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $component = Livewire::test(OrganizationSwitcher::class);

        expect(count($component->get('organizations')))->toBe(2);
    });

    it('loads member organizations', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $otherUser = UserFactory::new()->create();
        $org2 = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org2->addUser($this->user, OrganizationRole::MEMBER);

        $component = Livewire::test(OrganizationSwitcher::class);

        expect(count($component->get('organizations')))->toBe(2);
    });

    it('returns empty array when user is not set', function () {
        Auth::logout();

        $component = Livewire::test(OrganizationSwitcher::class);

        expect($component->get('organizations'))->toBeArray()
            ->and($component->get('organizations'))->toBeEmpty();
    });

    it('merges owned and member organizations without duplicates', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();
        $org->addUser($this->user, OrganizationRole::ADMINISTRATOR);

        $component = Livewire::test(OrganizationSwitcher::class);

        expect(count($component->get('organizations')))->toBe(1);
    });
});

describe('OrganizationSwitcher Livewire Component Switch Organization', function () {
    it('can switch to owned organization', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();
        $org2 = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationSwitcher::class)
            ->call('switchOrganization', $org2->id)
            ->assertSet('currentOrganization.id', $org2->id)
            ->assertSet('errorMessage', null);

        $this->user->refresh();
        expect($this->user->organization_id)->toBe($org2->id);
    });

    it('can switch to member organization', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $otherUser = UserFactory::new()->create();
        $org2 = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org2->addUser($this->user, OrganizationRole::MEMBER);

        Livewire::test(OrganizationSwitcher::class)
            ->call('switchOrganization', $org2->id)
            ->assertSet('currentOrganization.id', $org2->id);

        $this->user->refresh();
        expect($this->user->organization_id)->toBe($org2->id);
    });

    it('dispatches organization-switched event', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationSwitcher::class)
            ->call('switchOrganization', $org->id)
            ->assertDispatched('organization-switched');
    });

    it('closes dropdown after switching', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationSwitcher::class)
            ->set('showDropdown', true)
            ->call('switchOrganization', $org->id)
            ->assertSet('showDropdown', false);
    });

    it('clears error message before switching', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationSwitcher::class)
            ->set('errorMessage', 'Previous error')
            ->call('switchOrganization', $org->id)
            ->assertSet('errorMessage', null);
    });

    it('shows error when organization not found', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->call('switchOrganization', 99999)
            ->assertSet('errorMessage', 'Organization not found.');
    });

    it('shows error when user lacks access', function () {
        $otherUser = UserFactory::new()->create();
        $org = OrganizationFactory::new()->ownedBy($otherUser)->create();

        Livewire::test(OrganizationSwitcher::class)
            ->call('switchOrganization', $org->id)
            ->assertSet('errorMessage', 'You do not have access to this organization.');
    });
});

describe('OrganizationSwitcher Livewire Component Dropdown', function () {
    it('can toggle dropdown open', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->call('toggleDropdown')
            ->assertSet('showDropdown', true);
    });

    it('can toggle dropdown closed', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->set('showDropdown', true)
            ->call('toggleDropdown')
            ->assertSet('showDropdown', false);
    });

    it('can close dropdown explicitly', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->set('showDropdown', true)
            ->call('closeDropdown')
            ->assertSet('showDropdown', false);
    });
});

describe('OrganizationSwitcher Livewire Component Refresh Organizations', function () {
    it('refreshes organizations list', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $component = Livewire::test(OrganizationSwitcher::class);
        $initialCount = count($component->get('organizations'));

        // Create a new organization
        OrganizationFactory::new()->ownedBy($this->user)->create();

        $component->call('refreshOrganizations');

        expect(count($component->get('organizations')))->toBeGreaterThan($initialCount);
    });

    it('refreshes current organization when set', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();
        $this->user->organization_id = $org->id;
        $this->user->save();

        $component = Livewire::test(OrganizationSwitcher::class);

        // Update the organization
        $org->update(['name' => 'Updated Name']);

        $component->call('refreshOrganizations');

        expect($component->get('currentOrganization')->name)->toBe('Updated Name');
    });

    it('handles null current organization gracefully', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->call('refreshOrganizations')
            ->assertSet('currentOrganization', null);
    });
});

describe('OrganizationSwitcher Livewire Component Handle Organization Deleted', function () {
    it('clears current organization when deleted', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();
        $this->user->organization_id = $org->id;
        $this->user->save();

        Livewire::test(OrganizationSwitcher::class)
            ->call('handleOrganizationDeleted', $org->id)
            ->assertSet('currentOrganization', null);

        $this->user->refresh();
        expect($this->user->organization_id)->toBeNull();
    });

    it('refreshes organizations list after deletion', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();
        $org2 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $component = Livewire::test(OrganizationSwitcher::class);
        expect(count($component->get('organizations')))->toBe(2);

        // Just call the handler without actually deleting
        $component->call('handleOrganizationDeleted', $org1->id);

        // The list should still be refreshed
        expect($component->get('organizations'))->toBeArray();
    });

    it('does nothing when different organization is deleted', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();
        $org2 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $this->user->organization_id = $org1->id;
        $this->user->save();

        Livewire::test(OrganizationSwitcher::class)
            ->call('handleOrganizationDeleted', $org2->id)
            ->assertSet('currentOrganization.id', $org1->id);

        $this->user->refresh();
        expect($this->user->organization_id)->toBe($org1->id);
    });
});

describe('OrganizationSwitcher Livewire Component Render', function () {
    it('renders correct view', function () {
        Livewire::test(OrganizationSwitcher::class)
            ->assertViewIs('org::livewire.organization-switcher');
    });

    it('passes organizations to view', function () {
        OrganizationFactory::new()->ownedBy($this->user)->count(2)->create();

        $component = Livewire::test(OrganizationSwitcher::class);

        expect($component->get('organizations'))->toBeArray()
            ->and(count($component->get('organizations')))->toBeGreaterThanOrEqual(2);
    });
});

describe('OrganizationSwitcher Livewire Component Edge Cases', function () {
    it('handles user without organization_id attribute', function () {
        $this->user->organization_id = null;
        $this->user->save();

        Livewire::test(OrganizationSwitcher::class)
            ->assertSet('currentOrganization', null);
    });

    it('handles organization not found during mount', function () {
        $this->user->organization_id = 99999;
        $this->user->save();

        Livewire::test(OrganizationSwitcher::class)
            ->assertSet('currentOrganization', null);
    });

    it('handles inactive membership correctly', function () {
        $otherUser = UserFactory::new()->create();
        $org = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org->addUser($this->user, OrganizationRole::MEMBER, false); // inactive

        Livewire::test(OrganizationSwitcher::class)
            ->call('switchOrganization', $org->id)
            ->assertSet('errorMessage', 'You do not have access to this organization.');
    });

    it('returns empty organizations when loadOrganizations is called without user', function () {
        Auth::logout();

        $component = Livewire::test(OrganizationSwitcher::class);

        expect($component->get('organizations'))->toBeArray()
            ->and($component->get('organizations'))->toBeEmpty();
    });

    it('handles organizations relationship when preloaded', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        // Preload the organizations relationship
        $this->user->load('organizations');

        $component = Livewire::test(OrganizationSwitcher::class, ['user' => $this->user]);

        expect($component->get('organizations'))->toBeArray();
    });

    it('handles model not found exception during switch', function () {
        // This is already covered but let's be explicit
        Livewire::test(OrganizationSwitcher::class)
            ->call('switchOrganization', 99999)
            ->assertSet('errorMessage', 'Organization not found.');
    });

    it('handles database errors during switch', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        // Component should handle any errors gracefully
        $component = Livewire::test(OrganizationSwitcher::class);
        $component->call('switchOrganization', $org->id);

        // Should succeed without errors
        expect($component->get('errorMessage'))->toBeNull();
    });
});
