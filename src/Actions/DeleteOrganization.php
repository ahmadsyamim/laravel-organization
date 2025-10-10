<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteOrganization
{
    use AsAction;

    /**
     * Permanently delete an organization after validation checks.
     *
     * @param  Organization  $organization  The organization to delete
     * @param  User  $user  The user performing the deletion
     * @return array Result with success status and message
     *
     * @throws \Exception If deletion is not allowed
     */
    public function handle(Organization $organization, User $user): array
    {
        // Validate all business rules
        $this->validateDeletion($organization, $user);

        // Store the organization name before deletion
        $organizationName = $organization->name;
        $organizationId = $organization->id;

        // Permanently delete the organization
        $organization->forceDelete();

        return [
            'success' => true,
            'message' => "Organization '{$organizationName}' has been permanently deleted!",
            'deleted_organization_id' => $organizationId,
            'deleted_organization_name' => $organizationName,
        ];
    }

    /**
     * Validate all business rules before deletion.
     *
     * @param  Organization  $organization  The organization to delete
     * @param  User  $user  The user performing the deletion
     *
     * @throws \Exception If any validation rule fails
     */
    protected function validateDeletion(Organization $organization, User $user): void
    {
        // Rule 1: Only owner can delete
        if (! $organization->isOwnedBy($user)) {
            throw new \Exception('Only the organization owner can delete the organization.');
        }

        // Rule 2: User must have at least one organization
        $userOrganizationCount = Organization::where('owner_id', $user->id)->count();

        if ($userOrganizationCount <= 1) {
            throw new \Exception('Cannot delete your only organization. You must have at least one organization.');
        }

        // Rule 3: Cannot delete current organization
        if (property_exists($user, 'organization_id') &&
            $user->organization_id === $organization->id) {
            throw new \Exception('Cannot delete your current organization. Please switch to another organization first.');
        }

        // Rule 4: No active members (excluding owner)
        $activeMembersCount = $organization->activeUsers()
            ->where('user_id', '!=', $user->id)
            ->count();

        if ($activeMembersCount > 0) {
            throw new \Exception('Cannot delete organization with active members. Remove all members first.');
        }
    }

    /**
     * Check if organization can be deleted by the user.
     *
     * @param  Organization  $organization  The organization to check
     * @param  User  $user  The user attempting deletion
     * @return array Array with 'can_delete' boolean and 'reason' if false
     */
    public static function canDelete(Organization $organization, User $user): array
    {
        // Check ownership
        if (! $organization->isOwnedBy($user)) {
            return [
                'can_delete' => false,
                'reason' => 'Only the organization owner can delete the organization.',
            ];
        }

        // Check minimum organization count
        $userOrganizationCount = Organization::where('owner_id', $user->id)->count();

        if ($userOrganizationCount <= 1) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete your only organization. You must have at least one organization.',
            ];
        }

        // Check if current organization
        if (property_exists($user, 'organization_id') &&
            $user->organization_id === $organization->id) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete your current organization. Please switch to another organization first.',
            ];
        }

        // Check for active members
        $activeMembersCount = $organization->activeUsers()
            ->where('user_id', '!=', $user->id)
            ->count();

        if ($activeMembersCount > 0) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete organization with active members. Remove all members first.',
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null,
        ];
    }

    /**
     * Get deletion requirements for display purposes.
     *
     * @return array Array of requirement strings
     */
    public static function getDeletionRequirements(): array
    {
        return [
            'You must have at least one organization',
            'You cannot delete your currently active organization',
            'All members must be removed first',
            'This deletion is permanent and cannot be undone',
        ];
    }
}
