<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Events\OrganizationCreated;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateNewOrganization
{
    use AsAction;

    /**
     * Session key for storing current organization ID.
     */
    protected const ORGANIZATION_SESSION_KEY = 'organization_current_id';

    public string $commandSignature = 'organization:create {email} {--organization_name=} {--description=}';

    public string $commandDescription = 'Create a new organization for a user';

    /**
     * Handle the organization creation logic.
     *
     * @param  User  $user  The user for whom to create the organization
     * @param  bool  $default  Whether this should be the user's default organization
     * @param  string|null  $customName  Optional custom name for the organization
     * @param  string|null  $customDescription  Optional custom description for the organization
     * @return Organization The created organization instance
     *
     * @throws \InvalidArgumentException When trying to create a duplicate default organization
     */
    public function handle(User $user, bool $default = true, ?string $customName = null, ?string $customDescription = null): Organization
    {
        // Validate that user doesn't already have a default organization
        if ($default && ! $this->canCreateDefaultOrganization($user)) {
            throw new \InvalidArgumentException('User already has a default organization');
        }

        // Get user name safely (supports models with name attribute)
        /** @var string $userName */
        $userName = $user->getAttribute('name') ?? $user->getAttributeValue('email') ?? 'User';

        // Generate organization name: use custom name or default pattern
        $organizationName = $customName ?? (explode(' ', $userName, 2)[0]."'s Organization");

        // Generate organization description: use custom description or default pattern
        $organizationDescription = $customDescription ?? ($customName
            ? "Organization for {$userName}"
            : "Default organization for {$userName}");

        // Create the organization record
        $organization = Organization::create([
            'uuid' => Str::orderedUuid()->toString(),
            'name' => $organizationName,
            'slug' => Str::slug($organizationName).'-'.Str::lower(Str::random(6)),
            'description' => $organizationDescription,
            'owner_id' => $user->id,
        ]);

        // Set as user's default organization if this is a default organization
        if ($default) {
            $user->setAttribute('organization_id', $organization->id);
            $user->save();

            // Also set in session for immediate use (if session is available)
            if (function_exists('session')) {
                session([self::ORGANIZATION_SESSION_KEY => $organization->id]);
            }
        }

        // Dispatch the OrganizationCreated event
        OrganizationCreated::dispatch($organization);

        return $organization;
    }

    /**
     * Create a non-default organization for a user.
     *
     * This is a convenience method for creating additional organizations
     * that won't be set as the user's default organization.
     *
     * @param  User  $user  The user for whom to create the organization
     * @param  string  $name  The name of the organization
     * @param  string|null  $description  Optional description for the organization
     * @return Organization The created organization instance
     */
    public function createAdditionalOrganization(User $user, string $name, ?string $description = null): Organization
    {
        return $this->handle($user, false, $name, $description);
    }

    /**
     * Validate if a user can create a default organization.
     *
     * A user can create a default organization if they don't have one already
     * or if their current organization reference is invalid.
     *
     * @param  User  $user  The user to check
     * @return bool True if the user can create a default organization, false otherwise
     */
    public function canCreateDefaultOrganization(User $user): bool
    {
        // User can create default organization if they don't have one
        // or if the referenced organization no longer exists
        $organizationId = $user->getAttribute('organization_id');

        return ! $organizationId || ! Organization::find($organizationId);
    }

    /**
     * Execute the action as an Artisan command.
     *
     * @param  \Illuminate\Console\Command  $command
     */
    public function asCommand($command): void
    {
        $user = User::where('email', $command->argument('email'))->firstOrFail();

        $this->handle(
            $user,
            true,
            $command->option('organization_name'),
            $command->option('description')
        );

        $userEmail = $user->getAttribute('email');
        $command->info("Organization created successfully for {$userEmail}");
    }
}
