<?php

use CleaniqueCoders\LaravelOrganization\Livewire\ManageOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Workbench\App\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('prevents deletion when user has only one organization', function () {
    $organization = Organization::factory()->create(['owner_id' => $this->user->id]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization)
        ->set('confirmationName', $organization->name)
        ->call('deleteOrganization')
        ->assertSet('errorMessage', 'Cannot delete your only organization. You must have at least one organization.');

    expect(Organization::find($organization->id))->not->toBeNull();
});

it('allows deletion when user has multiple organizations', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization2)
        ->set('confirmationName', $organization2->name)
        ->call('deleteOrganization');

    expect(Organization::withTrashed()->find($organization2->id))->toBeNull();
    expect(Organization::find($organization1->id))->not->toBeNull();
});

it('prevents deletion of current organization', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    // Set organization1 as current
    $this->user->update(['organization_id' => $organization1->id]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization1)
        ->set('confirmationName', $organization1->name)
        ->call('deleteOrganization')
        ->assertSet('errorMessage', 'Cannot delete your current organization. Please switch to another organization first.');

    expect(Organization::find($organization1->id))->not->toBeNull();
});

it('allows deletion of non-current organization', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    // Set organization1 as current
    $this->user->update(['organization_id' => $organization1->id]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization2)
        ->set('confirmationName', $organization2->name)
        ->call('deleteOrganization');

    // Should be permanently deleted (not in database at all)
    expect(Organization::withTrashed()->find($organization2->id))->toBeNull();
    expect(Organization::find($organization1->id))->not->toBeNull();
});

it('permanently deletes organization using forceDelete', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization2)
        ->set('confirmationName', $organization2->name)
        ->call('deleteOrganization');

    // Check it's not soft deleted but completely removed
    expect(Organization::withTrashed()->find($organization2->id))->toBeNull();
});

it('prevents deletion when organization has active members', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $member = User::factory()->create();
    $organization2->users()->attach($member->id, [
        'role' => 'member',
        'is_active' => true,
    ]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization2)
        ->set('confirmationName', $organization2->name)
        ->call('deleteOrganization')
        ->assertSet('errorMessage', 'Cannot delete organization with active members. Remove all members first.');

    expect(Organization::find($organization2->id))->not->toBeNull();
});

it('requires exact organization name confirmation', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization2)
        ->set('confirmationName', 'wrong name')
        ->call('deleteOrganization')
        ->assertSet('errorMessage', 'Organization name does not match.')
        ->assertHasErrors('confirmationName');

    expect(Organization::find($organization2->id))->not->toBeNull();
});

it('only allows owner to delete organization', function () {
    $anotherUser = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $anotherUser->id]);

    Livewire::test(ManageOrganization::class)
        ->set('organization', $organization)
        ->set('confirmationName', $organization->name)
        ->call('deleteOrganization')
        ->assertSet('errorMessage', 'Only the organization owner can delete the organization.');

    expect(Organization::find($organization->id))->not->toBeNull();
});
