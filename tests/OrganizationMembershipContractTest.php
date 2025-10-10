<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Models\Organization;

it('implements OrganizationMembershipContract methods correctly', function () {
    $owner = UserFactory::new()->create();
    $user1 = UserFactory::new()->create();
    $user2 = UserFactory::new()->create();

    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
    ]);

    // Test adding users
    $organization->addUser($user1, OrganizationRole::MEMBER);
    $organization->addUser($user2, OrganizationRole::ADMINISTRATOR);

    expect($organization->hasMember($user1))->toBeTrue();
    expect($organization->hasMember($user2))->toBeTrue();
    expect($organization->hasActiveMember($user1))->toBeTrue();
    expect($organization->hasActiveMember($user2))->toBeTrue();

    // Test role checking
    expect($organization->userHasRole($user1, OrganizationRole::MEMBER))->toBeTrue();
    expect($organization->userHasRole($user2, OrganizationRole::ADMINISTRATOR))->toBeTrue();
    expect($organization->userHasRole($user1, OrganizationRole::ADMINISTRATOR))->toBeFalse();

    // Test getting user role
    expect($organization->getUserRole($user1))->toBe(OrganizationRole::MEMBER);
    expect($organization->getUserRole($user2))->toBe(OrganizationRole::ADMINISTRATOR);

    // Test updating user role
    $organization->updateUserRole($user1, OrganizationRole::ADMINISTRATOR);
    expect($organization->getUserRole($user1))->toBe(OrganizationRole::ADMINISTRATOR);

    // Test deactivating user
    $organization->setUserActiveStatus($user1, false);
    expect($organization->hasActiveMember($user1))->toBeFalse();
    expect($organization->hasMember($user1))->toBeTrue(); // Still a member, just inactive

    // Test removing user
    $organization->removeUser($user1);
    expect($organization->hasMember($user1))->toBeFalse();

    // Test relationship methods
    expect($organization->users())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    expect($organization->activeUsers())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    expect($organization->administrators())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    expect($organization->members())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);

    // Test getting all members
    $allMembers = $organization->allMembers();
    expect($allMembers)->toHaveCount(1); // Only user2 is active
    expect($allMembers->first()->id)->toBe($user2->id);
});
