<?php

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Models\User;

uses(RefreshDatabase::class);

it('prevents infinite recursion when accessing organization_id', function () {
    // Create organization first
    $organization = Organization::factory()->create([
        'owner_id' => 1, // Temporary owner
    ]);

    // Create a user with the organization
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    // Authenticate the user
    $this->actingAs($user);

    // The main test: query scoped models should work without recursion
    // This previously caused memory exhaustion
    $scopedModel = new class extends \Illuminate\Database\Eloquent\Model
    {
        use \CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganization;

        protected $table = 'test_scoped_models';

        protected $fillable = ['name', 'organization_id'];
    };

    // This should not cause infinite loop when the scope is applied
    expect(function () use ($scopedModel) {
        $sql = $scopedModel->newQuery()->toSql();
        // Verify the scope is applied
        expect($sql)->toContain('organization_id');
    })->not->toThrow(\Exception::class);

    // Also verify that accessing organization_id directly works
    expect($user->organization_id)->toBe($organization->id);

    // And that we can query the organization table
    expect(Organization::find($organization->id))->toBeInstanceOf(Organization::class);
});

it('safely retrieves organization_id without triggering relationships', function () {
    $user = User::factory()->create([
        'organization_id' => 123,
    ]);

    $this->actingAs($user);

    // The getCurrentOrganizationId should access raw attributes
    $organizationId = \CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganization::getCurrentOrganizationId();

    expect($organizationId)->toBe(123);
});

it('handles null organization_id gracefully', function () {
    $user = User::factory()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($user);

    $organizationId = \CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganization::getCurrentOrganizationId();

    expect($organizationId)->toBeNull();
});

it('returns null when user is not authenticated', function () {
    // No authenticated user
    $organizationId = \CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganization::getCurrentOrganizationId();

    expect($organizationId)->toBeNull();
});
