<?php

use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganization;
use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

// Create a test model that uses the InteractsWithOrganization trait
class TestTraitModel extends Model
{
    use InteractsWithOrganization;

    protected $table = 'test_trait_models';

    protected $fillable = ['name', 'organization_id'];
}

beforeEach(function () {
    // Create test table
    Schema::create('test_trait_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('organization_id')->nullable();
        $table->timestamps();
    });

    $this->user1 = UserFactory::new()->create();
    $this->user2 = UserFactory::new()->create();

    $this->org1 = OrganizationFactory::new()->ownedBy($this->user1)->create();
    $this->org2 = OrganizationFactory::new()->ownedBy($this->user2)->create();

    // Assign users to their organizations
    $this->user1->organization_id = $this->org1->id;
    $this->user2->organization_id = $this->org2->id;
});

afterEach(function () {
    Schema::dropIfExists('test_trait_models');
    Auth::logout();
});

describe('InteractsWithOrganization Trait Basic Functionality', function () {
    it('applies organization scope globally', function () {
        Auth::login($this->user1);

        // Create records for different organizations
        TestTraitModel::withoutGlobalScopes()->create(['name' => 'Record 1', 'organization_id' => $this->org1->id]);
        TestTraitModel::withoutGlobalScopes()->create(['name' => 'Record 2', 'organization_id' => $this->org2->id]);

        // Should only see org1 records when authenticated as user1
        $records = TestTraitModel::all();

        expect($records)->toHaveCount(1)
            ->and($records->first()->organization_id)->toBe($this->org1->id);
    });

    it('auto-assigns organization_id when creating records', function () {
        Auth::login($this->user1);

        $record = TestTraitModel::create(['name' => 'Test Record']);

        expect($record->organization_id)->toBe($this->org1->id);
    });

    it('does not override manually set organization_id', function () {
        Auth::login($this->user1);

        $record = TestTraitModel::create(['name' => 'Test Record', 'organization_id' => $this->org2->id]);

        expect($record->organization_id)->toBe($this->org2->id);
    });

    it('does not set organization_id when user is not authenticated', function () {
        Auth::logout();

        $record = TestTraitModel::create(['name' => 'Test Record']);

        expect($record->organization_id)->toBeNull();
    });

    it('does not set organization_id when user has no organization', function () {
        $userWithoutOrg = UserFactory::new()->create(['organization_id' => null]);
        Auth::login($userWithoutOrg);

        $record = TestTraitModel::create(['name' => 'Test Record']);

        expect($record->organization_id)->toBeNull();
    });
});

describe('InteractsWithOrganization Trait Relationships', function () {
    it('defines organization relationship', function () {
        $record = TestTraitModel::withoutGlobalScopes()->create(['name' => 'Test', 'organization_id' => $this->org1->id]);

        expect($record->organization)->toBeInstanceOf(Organization::class);
        // Note: This will fail due to config mismatch, but tests the relationship exists
    });
});

describe('InteractsWithOrganization Trait Query Scopes', function () {
    beforeEach(function () {
        // Create test data
        TestTraitModel::withoutGlobalScopes()->create(['name' => 'Org 1 Record 1', 'organization_id' => $this->org1->id]);
        TestTraitModel::withoutGlobalScopes()->create(['name' => 'Org 1 Record 2', 'organization_id' => $this->org1->id]);
        TestTraitModel::withoutGlobalScopes()->create(['name' => 'Org 2 Record', 'organization_id' => $this->org2->id]);
        TestTraitModel::withoutGlobalScopes()->create(['name' => 'No Org Record', 'organization_id' => null]);
    });

    it('can query all organizations using allOrganizations scope', function () {
        Auth::login($this->user1);

        $records = TestTraitModel::allOrganizations()->get();

        expect($records)->toHaveCount(4);
    });

    it('allOrganizations scope removes global organization scope', function () {
        Auth::login($this->user1);

        // Without allOrganizations, should only see org1 records (2)
        $scoped = TestTraitModel::count();
        expect($scoped)->toBe(2);

        // With allOrganizations, should see all records (4)
        $unscoped = TestTraitModel::allOrganizations()->count();
        expect($unscoped)->toBe(4);
    });

    it('can query specific organization using forOrganization scope', function () {
        Auth::login($this->user1);

        $records = TestTraitModel::forOrganization($this->org2->id)->get();

        expect($records)->toHaveCount(1)
            ->and($records->first()->name)->toBe('Org 2 Record');
    });

    it('forOrganization scope bypasses global organization scope', function () {
        Auth::login($this->user1);

        // Even though user1 is authenticated, can still query org2 records
        $records = TestTraitModel::forOrganization($this->org2->id)->get();

        expect($records)->toHaveCount(1)
            ->and($records->first()->organization_id)->toBe($this->org2->id);
    });

    it('can query records with null organization_id', function () {
        Auth::login($this->user1);

        $records = TestTraitModel::forOrganization(null)->get();

        expect($records)->toHaveCount(1)
            ->and($records->first()->name)->toBe('No Org Record');
    });
});

describe('InteractsWithOrganization Trait Static Methods', function () {
    it('can get current organization id from authenticated user', function () {
        Auth::login($this->user1);

        expect(TestTraitModel::getCurrentOrganizationId())->toBe($this->org1->id);
    });

    it('returns null when no user is authenticated', function () {
        Auth::logout();

        expect(TestTraitModel::getCurrentOrganizationId())->toBeNull();
    });

    it('returns null when authenticated user has no organization', function () {
        $userWithoutOrg = UserFactory::new()->create(['organization_id' => null]);
        Auth::login($userWithoutOrg);

        expect(TestTraitModel::getCurrentOrganizationId())->toBeNull();
    });
});

describe('InteractsWithOrganization Trait Integration', function () {
    it('works with standard eloquent operations', function () {
        Auth::login($this->user1);

        // Create
        $record = TestTraitModel::create(['name' => 'Test']);
        expect($record->organization_id)->toBe($this->org1->id);

        // Update
        $record->update(['name' => 'Updated Test']);
        expect($record->fresh()->name)->toBe('Updated Test');

        // Find
        $found = TestTraitModel::find($record->id);
        expect($found)->not->toBeNull()
            ->and($found->name)->toBe('Updated Test');

        // Delete
        $record->delete();
        expect(TestTraitModel::find($record->id))->toBeNull();
    });

    it('maintains scope when user switches organizations', function () {
        Auth::login($this->user1);

        // Create record as user1
        $record = TestTraitModel::create(['name' => 'Test']);
        expect(TestTraitModel::count())->toBe(1);

        // Switch user1 to org2
        $this->user1->organization_id = $this->org2->id;
        $this->user1->save();

        Auth::setUser($this->user1->fresh());

        // Should not see the old record
        expect(TestTraitModel::count())->toBe(0);

        // But can see it with allOrganizations
        expect(TestTraitModel::allOrganizations()->count())->toBe(1);
    });

    it('handles complex queries with trait scopes', function () {
        Auth::login($this->user1);

        TestTraitModel::create(['name' => 'Alpha']);
        TestTraitModel::create(['name' => 'Beta']);

        $records = TestTraitModel::where('name', 'like', '%eta%')
            ->orderBy('name')
            ->get();

        expect($records)->toHaveCount(1)
            ->and($records->first()->name)->toBe('Beta')
            ->and($records->first()->organization_id)->toBe($this->org1->id);
    });
});
