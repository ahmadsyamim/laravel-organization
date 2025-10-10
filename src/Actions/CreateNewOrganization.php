<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateNewOrganization
{
    use AsAction;

    /**
     * The Artisan command signature.
     *
     * Defines the command structure with:
     * - {email}: Required argument for the user's email address
     * - {--organization_name=}: Optional flag for custom organization name
     * - {--description=}: Optional flag for custom organization description
     */
    public string $commandSignature = 'user:create-org {email} {--organization_name= : Optional name for the organization} {--description= : Optional description for the organization}';

    /**
     * The Artisan command description shown in help output.
     */
    public string $commandDescription = 'Create organization for given user.';

    /**
     * Execute the action as an Artisan command.
     *
     * Command usage:
     * - php artisan user:create-org user@example.com (creates default organization)
     * - php artisan user:create-org user@example.com --organization_name="My Company" (creates additional organization)
     * - php artisan user:create-org user@example.com --organization_name="My Company" --description="A great company" (creates additional organization with custom description)
     *
     * @param  Command  $command  The Artisan command instance
     * @return void
     */
    public function asCommand(Command $command)
    {
        // Find the user by email or fail if not found
        $user = User::where('email', $command->argument('email'))->firstOrFail();
        $organizationName = $command->option('organization_name');
        $description = $command->option('description');

        // If organization_name option is not provided, create default organization
        // If organization_name option is provided, create additional organization
        $default = is_null($organizationName);

        $organization = $this->handle($user, $default, $organizationName, $description);

        $command->info("Organization '{$organization->name}' created successfully for user {$user->email}");
    }

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
        // Generate organization name: use custom name or default pattern
        $organizationName = $customName ?? (explode(' ', $user->name, 2)[0]."'s Organization");

        // Generate organization description: use custom description or default pattern
        $organizationDescription = $customDescription ?? ($customName
            ? "Organization for {$user->name}"
            : "Default organization for {$user->name}");

        // Create the organization record
        $organization = Organization::create([
            'uuid' => Str::orderedUuid()->toString(),
            'name' => $organizationName,
            'slug' => Str::slug($organizationName),
            'description' => $organizationDescription,
            'owner_id' => $user->id,
        ]);

        // Set as user's default organization if this is a default organization
        if ($default) {
            $user->organization_id = $organization->id;
            $user->save();
        }

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
        return ! $user->organization_id || ! Organization::find($user->organization_id);
    }
}
