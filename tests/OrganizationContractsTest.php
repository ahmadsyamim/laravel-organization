<?php

use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Models\Organization;

it('implements all organization contracts', function () {
    $organization = new Organization;

    expect($organization)
        ->toBeInstanceOf(OrganizationContract::class)
        ->toBeInstanceOf(OrganizationMembershipContract::class)
        ->toBeInstanceOf(OrganizationOwnershipContract::class)
        ->toBeInstanceOf(OrganizationSettingsContract::class);
});

it('can resolve organization contracts from container', function () {
    // Test that contracts can be resolved from the service container
    $organizationContract = app(OrganizationContract::class);
    $membershipContract = app(OrganizationMembershipContract::class);
    $ownershipContract = app(OrganizationOwnershipContract::class);
    $settingsContract = app(OrganizationSettingsContract::class);

    expect($organizationContract)->toBeInstanceOf(Organization::class);
    expect($membershipContract)->toBeInstanceOf(Organization::class);
    expect($ownershipContract)->toBeInstanceOf(Organization::class);
    expect($settingsContract)->toBeInstanceOf(Organization::class);
});

it('implements OrganizationContract methods correctly', function () {
    $organization = Organization::factory()->create([
        'name' => 'Test Organization',
        'slug' => 'test-organization',
        'description' => 'A test organization',
    ]);

    expect($organization->getId())->toBe($organization->id);
    expect($organization->getUuid())->toBe($organization->uuid);
    expect($organization->getName())->toBe('Test Organization');
    expect($organization->getSlug())->toBe('test-organization');
    expect($organization->getDescription())->toBe('A test organization');
    expect($organization->isActive())->toBeTrue();

    // Test soft delete
    $organization->delete();
    expect($organization->isActive())->toBeFalse();
});

it('implements OrganizationOwnershipContract methods correctly', function () {
    $user1 = UserFactory::new()->create();
    $user2 = UserFactory::new()->create();

    $organization = Organization::factory()->create([
        'owner_id' => $user1->id,
    ]);

    expect($organization->getOwnerId())->toBe($user1->id);
    expect($organization->isOwnedBy($user1))->toBeTrue();
    expect($organization->isOwnedBy($user2))->toBeFalse();

    // Test setting owner
    $organization->setOwner($user2);
    expect($organization->getOwnerId())->toBe($user2->id);

    // Test transferring ownership
    $organization->transferOwnership($user1);
    expect($organization->getOwnerId())->toBe($user1->id);

    // Refresh from database to ensure it was saved
    $organization->refresh();
    expect($organization->owner_id)->toBe($user1->id);
});

it('implements OrganizationSettingsContract methods correctly', function () {
    $organization = Organization::factory()->create();

    // Test default settings
    expect($organization->getAllSettings())->toBeArray();

    // Test setting and getting values
    $organization->setSetting('test.key', 'test value');
    expect($organization->getSetting('test.key'))->toBe('test value');
    expect($organization->hasSetting('test.key'))->toBeTrue();
    expect($organization->getSetting('nonexistent.key', 'default'))->toBe('default');

    // Test merging settings
    $organization->mergeSettings([
        'new' => ['setting' => 'value'],
        'test' => ['another' => 'key'],
    ]);

    expect($organization->getSetting('new.setting'))->toBe('value');
    expect($organization->getSetting('test.another'))->toBe('key');
    expect($organization->getSetting('test.key'))->toBe('test value'); // Should preserve existing

    // Test removing setting
    $organization->removeSetting('test.key');
    expect($organization->hasSetting('test.key'))->toBeFalse();

    // Test reset to defaults
    $organization->resetSettingsToDefaults();
    $defaultSettings = Organization::getDefaultSettings();
    expect($organization->getAllSettings())->toBe($defaultSettings);
});
