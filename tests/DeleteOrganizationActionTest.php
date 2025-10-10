<?php

use CleaniqueCoders\LaravelOrganization\Actions\DeleteOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can delete organization with valid conditions', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $result = DeleteOrganization::run($organization2, $this->user);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('permanently deleted')
        ->and($result['deleted_organization_id'])->toBe($organization2->id)
        ->and($result['deleted_organization_name'])->toBe($organization2->name)
        ->and(Organization::withTrashed()->find($organization2->id))->toBeNull();
});

it('prevents deletion when user has only one organization', function () {
    $organization = Organization::factory()->create(['owner_id' => $this->user->id]);

    DeleteOrganization::run($organization, $this->user);
})->throws(Exception::class, 'Cannot delete your only organization');

it('prevents deletion of current organization', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $this->user->update(['organization_id' => $organization1->id]);

    DeleteOrganization::run($organization1, $this->user);
})->throws(Exception::class, 'Cannot delete your current organization');

it('prevents non-owner from deleting organization', function () {
    $anotherUser = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $anotherUser->id]);

    DeleteOrganization::run($organization, $this->user);
})->throws(Exception::class, 'Only the organization owner can delete');

it('prevents deletion when organization has active members', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $member = User::factory()->create();
    $organization2->users()->attach($member->id, [
        'role' => 'member',
        'is_active' => true,
    ]);

    DeleteOrganization::run($organization2, $this->user);
})->throws(Exception::class, 'Cannot delete organization with active members');

it('allows deletion when members are inactive', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $member = User::factory()->create();
    $organization2->users()->attach($member->id, [
        'role' => 'member',
        'is_active' => false,
    ]);

    $result = DeleteOrganization::run($organization2, $this->user);

    expect($result['success'])->toBeTrue()
        ->and(Organization::withTrashed()->find($organization2->id))->toBeNull();
});

it('permanently deletes using forceDelete', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    DeleteOrganization::run($organization2, $this->user);

    // Should not exist even when checking with soft deletes
    expect(Organization::withTrashed()->find($organization2->id))->toBeNull();
});

it('provides canDelete method for checking deletion eligibility', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $result = DeleteOrganization::canDelete($organization2, $this->user);

    expect($result)->toBeArray()
        ->and($result['can_delete'])->toBeTrue()
        ->and($result['reason'])->toBeNull();
});

it('returns false from canDelete when only one organization', function () {
    $organization = Organization::factory()->create(['owner_id' => $this->user->id]);

    $result = DeleteOrganization::canDelete($organization, $this->user);

    expect($result['can_delete'])->toBeFalse()
        ->and($result['reason'])->toContain('only organization');
});

it('returns false from canDelete for current organization', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $this->user->update(['organization_id' => $organization1->id]);

    $result = DeleteOrganization::canDelete($organization1, $this->user);

    expect($result['can_delete'])->toBeFalse()
        ->and($result['reason'])->toContain('current organization');
});

it('returns false from canDelete when has active members', function () {
    $organization1 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $organization2 = Organization::factory()->create(['owner_id' => $this->user->id]);

    $member = User::factory()->create();
    $organization2->users()->attach($member->id, [
        'role' => 'member',
        'is_active' => true,
    ]);

    $result = DeleteOrganization::canDelete($organization2, $this->user);

    expect($result['can_delete'])->toBeFalse()
        ->and($result['reason'])->toContain('active members');
});

it('returns false from canDelete for non-owner', function () {
    $anotherUser = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $anotherUser->id]);

    $result = DeleteOrganization::canDelete($organization, $this->user);

    expect($result['can_delete'])->toBeFalse()
        ->and($result['reason'])->toContain('owner');
});

it('provides getDeletionRequirements method', function () {
    $requirements = DeleteOrganization::getDeletionRequirements();

    expect($requirements)->toBeArray()
        ->and($requirements)->toHaveCount(4)
        ->and($requirements[0])->toContain('at least one organization')
        ->and($requirements[1])->toContain('currently active')
        ->and($requirements[2])->toContain('members must be removed')
        ->and($requirements[3])->toContain('permanent');
});
