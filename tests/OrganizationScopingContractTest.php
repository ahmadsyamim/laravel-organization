<?php

use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganization;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationScopingContract;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Create a test model that uses the trait
class ContractTestScopedModel extends Model implements OrganizationScopingContract
{
    use InteractsWithOrganization;

    protected $table = 'contract_test_scoped_models';

    protected $fillable = ['name', 'organization_id'];
}

beforeEach(function () {
    // Create test table
    \Illuminate\Support\Facades\Schema::create('contract_test_scoped_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('organization_id');
        $table->timestamps();
    });
});

afterEach(function () {
    \Illuminate\Support\Facades\Schema::dropIfExists('contract_test_scoped_models');
});

it('implements OrganizationScopingContract correctly', function () {
    $model = new ContractTestScopedModel;

    expect($model)->toBeInstanceOf(OrganizationScopingContract::class);
});

it('provides organization relationship', function () {
    $organization = Organization::factory()->create();
    $model = new ContractTestScopedModel([
        'name' => 'Test Model',
        'organization_id' => $organization->id,
    ]);

    expect($model->organization())->toBeInstanceOf(BelongsTo::class);
    expect($model->getOrganizationId())->toBe($organization->id);
});

it('provides scoping methods', function () {
    $organization1 = Organization::factory()->create();
    $organization2 = Organization::factory()->create();

    ContractTestScopedModel::create([
        'name' => 'Model 1',
        'organization_id' => $organization1->id,
    ]);

    ContractTestScopedModel::create([
        'name' => 'Model 2',
        'organization_id' => $organization2->id,
    ]);

    // Test allOrganizations scope
    $allModels = ContractTestScopedModel::allOrganizations()->get();
    expect($allModels)->toHaveCount(2);

    // Test forOrganization scope
    $org1Models = ContractTestScopedModel::forOrganization($organization1->id)->get();
    expect($org1Models)->toHaveCount(1);
    expect($org1Models->first()->name)->toBe('Model 1');

    $org2Models = ContractTestScopedModel::forOrganization($organization2->id)->get();
    expect($org2Models)->toHaveCount(1);
    expect($org2Models->first()->name)->toBe('Model 2');
});

it('provides getCurrentOrganizationId method', function () {
    // This method depends on auth, so we'll just test it exists
    expect(ContractTestScopedModel::getCurrentOrganizationId())->toBeNull(); // No auth user in tests
});
