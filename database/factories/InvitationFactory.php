<?php

namespace CleaniqueCoders\LaravelOrganization\Database\Factories;

use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\CleaniqueCoders\LaravelOrganization\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Invitation>
     */
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'invited_by_user_id' => UserFactory::new(),
            'user_id' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'token' => Str::random(40),
            'role' => OrganizationRole::MEMBER->value,
            'accepted_at' => null,
            'declined_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * State for a pending invitation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => null,
            'declined_at' => null,
        ]);
    }

    /**
     * State for an accepted invitation.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => UserFactory::new(),
            'accepted_at' => now()->subDay(),
            'declined_at' => null,
        ]);
    }

    /**
     * State for a declined invitation.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => null,
            'declined_at' => now()->subDay(),
        ]);
    }

    /**
     * State for an expired invitation.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * State for an admin role invitation.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => OrganizationRole::ADMINISTRATOR->value,
        ]);
    }

    /**
     * State for a member role invitation.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => OrganizationRole::MEMBER->value,
        ]);
    }

    /**
     * Relationship to set invited by user.
     */
    public function forInvitedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'invited_by_user_id' => $user->id,
        ]);
    }
}
