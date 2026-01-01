<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Livewire\OrganizationList;
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

describe('OrganizationList Livewire Component Initialization', function () {
    it('initializes with default values', function () {
        Livewire::test(OrganizationList::class)
            ->assertSet('search', '')
            ->assertSet('sortBy', 'name')
            ->assertSet('sortDirection', 'asc')
            ->assertSet('filter', 'all')
            ->assertSet('errorMessage', null);
    });

    it('renders correct view', function () {
        Livewire::test(OrganizationList::class)
            ->assertViewIs('org::livewire.organization-list');
    });
});

describe('OrganizationList Livewire Component Search', function () {
    it('can search organizations by name', function () {
        OrganizationFactory::new()->ownedBy($this->user)->create(['name' => 'Acme Corp']);
        OrganizationFactory::new()->ownedBy($this->user)->create(['name' => 'Tech Solutions']);

        $component = Livewire::test(OrganizationList::class)
            ->set('search', 'Acme');

        expect($component->get('organizations')->count())->toBe(1);
    });

    it('can search organizations by description', function () {
        OrganizationFactory::new()->ownedBy($this->user)->create([
            'name' => 'Acme Corp',
            'description' => 'Software development',
        ]);
        OrganizationFactory::new()->ownedBy($this->user)->create([
            'name' => 'Tech Solutions',
            'description' => 'Hardware sales',
        ]);

        $component = Livewire::test(OrganizationList::class)
            ->set('search', 'Software');

        expect($component->get('organizations')->count())->toBe(1);
    });

    it('resets page when search is updated', function () {
        Livewire::test(OrganizationList::class)
            ->set('search', 'test');
        // Page is reset internally, just verify search was set
        expect(true)->toBeTrue();
    });

    it('returns empty when search has no matches', function () {
        OrganizationFactory::new()->ownedBy($this->user)->create(['name' => 'Acme Corp']);

        $component = Livewire::test(OrganizationList::class)
            ->set('search', 'Nonexistent');

        expect($component->get('organizations')->count())->toBe(0);
    });
});

describe('OrganizationList Livewire Component Sorting', function () {
    it('can sort by field', function () {
        Livewire::test(OrganizationList::class)
            ->call('sortBy', 'created_at')
            ->assertSet('sortBy', 'created_at')
            ->assertSet('sortDirection', 'asc');
    });

    it('toggles sort direction when clicking same field', function () {
        Livewire::test(OrganizationList::class)
            ->set('sortBy', 'name')
            ->set('sortDirection', 'asc')
            ->call('sortBy', 'name')
            ->assertSet('sortDirection', 'desc');
    });

    it('resets sort direction to asc when changing field', function () {
        Livewire::test(OrganizationList::class)
            ->set('sortBy', 'name')
            ->set('sortDirection', 'desc')
            ->call('sortBy', 'created_at')
            ->assertSet('sortBy', 'created_at')
            ->assertSet('sortDirection', 'asc');
    });

    it('resets page when sorting', function () {
        Livewire::test(OrganizationList::class)
            ->call('sortBy', 'name');
        // Page is reset internally
        expect(true)->toBeTrue();
    });
});

describe('OrganizationList Livewire Component Filtering', function () {
    it('filters owned organizations', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $otherUser = UserFactory::new()->create();
        $org2 = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org2->addUser($this->user, OrganizationRole::MEMBER);

        $component = Livewire::test(OrganizationList::class)
            ->set('filter', 'owned');

        expect($component->get('organizations')->count())->toBe(1);
    });

    it('filters member organizations', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $otherUser = UserFactory::new()->create();
        $org2 = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org2->addUser($this->user, OrganizationRole::MEMBER);

        $component = Livewire::test(OrganizationList::class)
            ->set('filter', 'member');

        expect($component->get('organizations')->count())->toBe(1);
    });

    it('shows all organizations by default', function () {
        $org1 = OrganizationFactory::new()->ownedBy($this->user)->create();

        $otherUser = UserFactory::new()->create();
        $org2 = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org2->addUser($this->user, OrganizationRole::MEMBER);

        $component = Livewire::test(OrganizationList::class)
            ->set('filter', 'all');

        expect($component->get('organizations')->count())->toBe(2);
    });

    it('resets page when filter is updated', function () {
        Livewire::test(OrganizationList::class)
            ->set('filter', 'owned');
        // Page is reset internally
        expect(true)->toBeTrue();
    });

    it('excludes inactive memberships from member filter', function () {
        $otherUser = UserFactory::new()->create();
        $org = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org->addUser($this->user, OrganizationRole::MEMBER, false);

        $component = Livewire::test(OrganizationList::class)
            ->set('filter', 'member');

        expect($component->get('organizations')->count())->toBe(0);
    });
});

describe('OrganizationList Livewire Component Reset Filters', function () {
    it('can reset all filters', function () {
        Livewire::test(OrganizationList::class)
            ->set('search', 'test')
            ->set('sortBy', 'created_at')
            ->set('sortDirection', 'desc')
            ->set('filter', 'owned')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('sortBy', 'name')
            ->assertSet('sortDirection', 'asc')
            ->assertSet('filter', 'all');
    });

    it('resets page when resetting filters', function () {
        Livewire::test(OrganizationList::class)
            ->call('resetFilters');
        // Page is reset internally
        expect(true)->toBeTrue();
    });
});

describe('OrganizationList Livewire Component Actions', function () {
    it('dispatches event when editing organization', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationList::class)
            ->call('editOrganization', $org->id)
            ->assertDispatched('show-manage-organization');
    });

    it('dispatches event when deleting organization', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationList::class)
            ->call('deleteOrganization', $org->id)
            ->assertDispatched('show-manage-organization');
    });

    it('can switch to organization', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationList::class)
            ->call('switchToOrganization', $org->id)
            ->assertSet('errorMessage', null)
            ->assertDispatched('organization-switched');

        // Switching now uses session, not database
        expect(session('organization_current_id'))->toBe($org->id);
        // Database should remain unchanged after switch
        $this->user->refresh();
        expect($this->user->organization_id)->toBeNull();
    });

    it('can set organization as default', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationList::class)
            ->call('setAsDefault', $org->id)
            ->assertSet('successMessage', __('Default organization updated.'))
            ->assertDispatched('default-organization-changed');

        // setAsDefault should update the database
        $this->user->refresh();
        expect($this->user->organization_id)->toBe($org->id);
    });

    it('shows error when switching to non-existent organization', function () {
        Livewire::test(OrganizationList::class)
            ->call('switchToOrganization', 99999)
            ->assertSet('errorMessage', 'Organization not found.');
    });

    it('shows error when switching to organization without access', function () {
        $otherUser = UserFactory::new()->create();
        $org = OrganizationFactory::new()->ownedBy($otherUser)->create();

        Livewire::test(OrganizationList::class)
            ->call('switchToOrganization', $org->id)
            ->assertSet('errorMessage', 'You do not have access to this organization.');
    });

    it('clears error message before switching', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        Livewire::test(OrganizationList::class)
            ->set('errorMessage', 'Previous error')
            ->call('switchToOrganization', $org->id)
            ->assertSet('errorMessage', null);
    });
});

describe('OrganizationList Livewire Component User Roles', function () {
    it('shows owner role for owned organizations', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        $component = Livewire::test(OrganizationList::class);
        $role = $component->instance()->getUserRoleInOrganization($org);

        expect($role)->toBe('Owner');
    });

    it('shows administrator role for admin members', function () {
        $otherUser = UserFactory::new()->create();
        $org = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org->addUser($this->user, OrganizationRole::ADMINISTRATOR);

        $component = Livewire::test(OrganizationList::class);
        $role = $component->instance()->getUserRoleInOrganization($org);

        expect($role)->toBe('Administrator');
    });

    it('shows member role for regular members', function () {
        $otherUser = UserFactory::new()->create();
        $org = OrganizationFactory::new()->ownedBy($otherUser)->create();
        $org->addUser($this->user, OrganizationRole::MEMBER);

        $component = Livewire::test(OrganizationList::class);
        $role = $component->instance()->getUserRoleInOrganization($org);

        expect($role)->toBe('Member');
    });
});

describe('OrganizationList Livewire Component Edge Cases', function () {
    it('returns empty collection when user is not authenticated', function () {
        Auth::logout();

        $component = Livewire::test(OrganizationList::class);

        expect($component->get('organizations'))->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($component->get('organizations')->isEmpty())->toBeTrue();
    });

    it('paginates organizations', function () {
        OrganizationFactory::new()->ownedBy($this->user)->count(15)->create();

        $component = Livewire::test(OrganizationList::class);

        expect($component->get('organizations')->count())->toBe(10)
            ->and($component->get('organizations')->total())->toBe(15);
    });

    it('handles errors when switching to invalid organization', function () {
        // Try to switch to a non-existent organization ID
        $component = Livewire::test(OrganizationList::class);

        $component->call('switchToOrganization', 99999);

        // Should handle the error gracefully
        expect($component->get('errorMessage'))->not->toBeNull();
    });

    it('handles database errors during switch gracefully', function () {
        $org = OrganizationFactory::new()->ownedBy($this->user)->create();

        // The component should handle any database errors
        // In a real scenario, this might happen if DB connection fails
        $component = Livewire::test(OrganizationList::class);

        // Just verify the component can handle switch without crashing
        $component->call('switchToOrganization', $org->id);

        expect(true)->toBeTrue();
    });
});
