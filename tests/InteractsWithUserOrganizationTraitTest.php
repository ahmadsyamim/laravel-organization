<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Models\Organization;

beforeEach(function () {
    $this->user1 = UserFactory::new()->create();
    $this->user2 = UserFactory::new()->create();
    $this->user3 = UserFactory::new()->create();

    $this->org1 = OrganizationFactory::new()->ownedBy($this->user1)->create();
    $this->org2 = OrganizationFactory::new()->ownedBy($this->user2)->create();
    $this->org3 = OrganizationFactory::new()->ownedBy($this->user3)->create();

    // Set current organization for users
    $this->user1->organization_id = $this->org1->id;
    $this->user1->save();

    $this->user2->organization_id = $this->org2->id;
    $this->user2->save();
});

describe('InteractsWithUserOrganization Trait Organization ID Management', function () {
    it('can get organization ID', function () {
        expect($this->user1->getOrganizationId())->toBe($this->org1->id);
    });

    it('can set organization ID', function () {
        $this->user1->setOrganizationId($this->org2->id);

        expect($this->user1->getOrganizationId())->toBe($this->org2->id);
    });

    it('returns null when organization ID is not set', function () {
        $user = UserFactory::new()->create(['organization_id' => null]);

        expect($user->getOrganizationId())->toBeNull();
    });
});

describe('InteractsWithUserOrganization Trait Organization Relationships', function () {
    it('defines organizations relationship', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);
        $this->org3->addUser($this->user2, OrganizationRole::MEMBER);

        $organizations = $this->user2->organizations;

        expect($organizations)->toHaveCount(2)
            ->and($organizations->pluck('id')->toArray())->toContain($this->org1->id, $this->org3->id);
    });

    it('defines ownedOrganizations relationship', function () {
        $ownedOrgs = $this->user1->ownedOrganizations;

        expect($ownedOrgs)->toHaveCount(1)
            ->and($ownedOrgs->first()->id)->toBe($this->org1->id);
    });

    it('defines currentOrganization relationship', function () {
        expect($this->user1->currentOrganization)->toBeInstanceOf(Organization::class)
            ->and($this->user1->currentOrganization->id)->toBe($this->org1->id);
    });

    it('returns null for currentOrganization when not set', function () {
        $user = UserFactory::new()->create(['organization_id' => null]);

        expect($user->currentOrganization)->toBeNull();
    });
});

describe('InteractsWithUserOrganization Trait Organization Membership', function () {
    it('can check if user belongs to organization as owner', function () {
        expect($this->user1->belongsToOrganization($this->org1->id))->toBeTrue();
    });

    it('can check if user belongs to organization as member', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);

        expect($this->user2->belongsToOrganization($this->org1->id))->toBeTrue();
    });

    it('returns false if user does not belong to organization', function () {
        expect($this->user2->belongsToOrganization($this->org1->id))->toBeFalse();
    });

    it('can get only active organizations', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);
        $this->org3->addUser($this->user2, OrganizationRole::MEMBER);

        // Make one membership inactive
        $this->user2->organizations()->updateExistingPivot($this->org3->id, ['is_active' => false]);

        $activeOrgs = $this->user2->activeOrganizations;

        expect($activeOrgs)->toHaveCount(1) // Only org1 is active (org3 is inactive)
            ->and($activeOrgs->pluck('id')->toArray())->toContain($this->org1->id)
            ->and($activeOrgs->pluck('id')->toArray())->not->toContain($this->org3->id);
    });

    it('can get organizations where user is administrator', function () {
        $this->org1->addUser($this->user2, OrganizationRole::ADMINISTRATOR);
        $this->org3->addUser($this->user2, OrganizationRole::MEMBER);

        $adminOrgs = $this->user2->administratedOrganizations;

        expect($adminOrgs)->toHaveCount(1)
            ->and($adminOrgs->first()->id)->toBe($this->org1->id);
    });
});

describe('InteractsWithUserOrganization Trait Role Checking', function () {
    it('can check if user has specific role in organization', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);

        expect($this->user2->hasRoleInOrganization($this->org1->id, 'member'))->toBeTrue();
    });

    it('returns false if user has different role', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);

        expect($this->user2->hasRoleInOrganization($this->org1->id, 'administrator'))->toBeFalse();
    });

    it('returns false if user is not member of organization', function () {
        expect($this->user2->hasRoleInOrganization($this->org1->id, 'member'))->toBeFalse();
    });

    it('returns false if user membership is inactive', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);
        $this->user2->organizations()->updateExistingPivot($this->org1->id, ['is_active' => false]);

        expect($this->user2->hasRoleInOrganization($this->org1->id, 'member'))->toBeFalse();
    });
});

describe('InteractsWithUserOrganization Trait Ownership Checking', function () {
    it('can check if user owns organization', function () {
        expect($this->user1->ownsOrganization($this->org1->id))->toBeTrue();
    });

    it('returns false if user does not own organization', function () {
        expect($this->user2->ownsOrganization($this->org1->id))->toBeFalse();
    });

    it('can check if user is administrator of organization as owner', function () {
        expect($this->user1->isAdministratorOf($this->org1->id))->toBeTrue();
    });

    it('can check if user is administrator of organization with admin role', function () {
        $this->org1->addUser($this->user2, OrganizationRole::ADMINISTRATOR);

        expect($this->user2->isAdministratorOf($this->org1->id))->toBeTrue();
    });

    it('returns false if user is not administrator', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);

        expect($this->user2->isAdministratorOf($this->org1->id))->toBeFalse();
    });
});

describe('InteractsWithUserOrganization Trait Member Checking', function () {
    it('can check if user is member of organization as owner', function () {
        expect($this->user1->isMemberOf($this->org1->id))->toBeTrue();
    });

    it('can check if user is member of organization with member role', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);

        expect($this->user2->isMemberOf($this->org1->id))->toBeTrue();
    });

    it('can check if user is member of organization with administrator role', function () {
        $this->org1->addUser($this->user2, OrganizationRole::ADMINISTRATOR);

        expect($this->user2->isMemberOf($this->org1->id))->toBeTrue();
    });

    it('returns false if user is not member of organization', function () {
        expect($this->user2->isMemberOf($this->org1->id))->toBeFalse();
    });
});

describe('InteractsWithUserOrganization Trait Pivot Data', function () {
    it('includes role in pivot data', function () {
        $this->org1->addUser($this->user2, OrganizationRole::ADMINISTRATOR);

        $membership = $this->user2->organizations()->where('organization_id', $this->org1->id)->first();

        expect($membership->pivot->role)->toBe('administrator');
    });

    it('includes is_active in pivot data', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);

        $membership = $this->user2->organizations()->where('organization_id', $this->org1->id)->first();

        expect($membership->pivot->is_active)->toBe(1); // Database stores as integer
    });

    it('includes timestamps in pivot data', function () {
        $this->org1->addUser($this->user2, OrganizationRole::MEMBER);

        $membership = $this->user2->organizations()->where('organization_id', $this->org1->id)->first();

        expect($membership->pivot->created_at)->not->toBeNull()
            ->and($membership->pivot->updated_at)->not->toBeNull();
    });
});
