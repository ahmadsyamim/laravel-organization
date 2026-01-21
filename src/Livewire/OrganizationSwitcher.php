<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\LaravelOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class OrganizationSwitcher extends Component
{
    public ?Authenticatable $user = null;

    public ?Organization $currentOrganization = null;

    public $organizations = [];

    public bool $showDropdown = false;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public bool $isCurrentDefault = false;

    public function mount(mixed $user = null): void
    {
        // Use passed user or fallback to Auth::user()
        $this->user = $user ?? Auth::user();

        // Load current organization - check session first, then DB
        if ($this->user) {
            $organizationId = LaravelOrganization::getCurrentOrganizationId();

            if ($organizationId) {
                $this->currentOrganization = Organization::find($organizationId);
                $this->updateDefaultStatus();
            }
        }

        $this->loadOrganizations();
    }

    /**
     * Check if current organization is the user's default.
     */
    protected function updateDefaultStatus(): void
    {
        if (! $this->currentOrganization || ! $this->user instanceof Model) {
            $this->isCurrentDefault = false;

            return;
        }

        $defaultOrgId = $this->user->getAttribute('organization_id');
        $this->isCurrentDefault = $defaultOrgId === $this->currentOrganization->id;
    }

    public function loadOrganizations()
    {
        if (! $this->user) {
            $this->organizations = [];

            return;
        }

        $userId = $this->user instanceof Model ? $this->user->getKey() : null;

        if (! $userId) {
            $this->organizations = [];

            return;
        }

        // Get organizations where user is owner or member
        $ownedOrganizations = Organization::where('owner_id', $userId)->get();

        // Get organizations where user is a member (if the relationship exists)
        $memberOrganizations = collect([]);
        if ($this->user instanceof Model) {
            try {
                // Attempt to access organizations relationship if it exists
                $memberOrganizations = $this->user->relationLoaded('organizations')
                    ? $this->user->getRelation('organizations')
                    : $this->user->organizations()->get();
            } catch (\BadMethodCallException) {
                // Relationship doesn't exist, keep empty collection
            }
        }

        $this->organizations = $ownedOrganizations->merge($memberOrganizations)->unique('id')->all();
    }

    /**
     * Switch to a different organization (session-based, no DB write).
     */
    public function switchOrganization(int $organizationId): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $organization = Organization::find($organizationId);

            if (! $organization) {
                $this->errorMessage = __('Organization not found.');

                return;
            }

            // Check if user has access to this organization (cast to User for type safety)
            if ($this->user instanceof \Illuminate\Foundation\Auth\User) {
                if (! $organization->isOwnedBy($this->user) && ! $organization->hasActiveMember($this->user)) {
                    $this->errorMessage = __('You do not have access to this organization.');

                    return;
                }
            }

            // Store in session only (no DB write)
            LaravelOrganization::setCurrentOrganizationId($organization->id);

            $this->currentOrganization = $organization;
            $this->showDropdown = false;
            $this->updateDefaultStatus();

            // Emit event for other components to listen to
            $this->dispatch('organization-switched', organizationId: $organization->id);
        } catch (ModelNotFoundException $e) {
            Log::warning('Organization not found during switch', [
                'organization_id' => $organizationId,
                'user_id' => $this->user->getAuthIdentifier(),
            ]);
            $this->errorMessage = __('Organization not found.');
        } catch (\Throwable $e) {
            Log::error('Failed to switch organization', [
                'organization_id' => $organizationId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = __('Failed to switch organization. Please try again.');
        }
    }

    /**
     * Set the current organization as the user's default (persisted to DB).
     */
    public function setAsDefault()
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        if (! $this->currentOrganization) {
            $this->errorMessage = __('No organization selected.');

            return;
        }

        try {
            if ($this->user instanceof Model) {
                $this->user->setAttribute('organization_id', $this->currentOrganization->id);
                $this->user->save();
                $this->user->refresh();

                // Update session to keep in sync
                LaravelOrganization::setCurrentOrganizationId($this->currentOrganization->id);

                $this->isCurrentDefault = true;
                $this->successMessage = __('Default organization updated.');

                $this->dispatch('default-organization-changed', organizationId: $this->currentOrganization->id);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to set default organization', [
                'organization_id' => $this->currentOrganization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = __('Failed to set default organization. Please try again.');
        }
    }

    public function toggleDropdown()
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    /**
     * Refresh the organizations list when an organization is created or updated.
     */
    #[On('organization-created')]
    #[On('organization-updated')]
    public function refreshOrganizations()
    {
        $this->loadOrganizations();

        // Refresh current organization if it was updated
        if ($this->currentOrganization) {
            $this->currentOrganization = Organization::find($this->currentOrganization->id);
        }
    }

    /**
     * Handle organization deletion event.
     *
     * @param  int  $organizationId  The ID of the deleted organization
     */
    #[On('organization-deleted')]
    public function handleOrganizationDeleted(int $organizationId): void
    {
        // If the deleted organization was the current one, clear it
        if ($this->currentOrganization && $this->currentOrganization->id === $organizationId) {
            $this->currentOrganization = null;

            // Clear session
            LaravelOrganization::clearSession();

            // Only update DB if the deleted org was the default
            if ($this->user instanceof Model) {
                $defaultOrgId = $this->user->getAttribute('organization_id');
                if ($defaultOrgId === $organizationId) {
                    $this->user->update(['organization_id' => null]);
                }
            }

            $this->isCurrentDefault = false;
        }

        // Refresh the organizations list
        $this->loadOrganizations();
    }

    public function render()
    {
        return view('org::livewire.organization-switcher');
    }
}
