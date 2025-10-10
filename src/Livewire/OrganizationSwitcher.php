<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrganizationSwitcher extends Component
{
    public ?Authenticatable $user = null;

    public ?Organization $currentOrganization = null;

    public $organizations = [];

    public bool $showDropdown = false;

    public ?string $errorMessage = null;

    protected $listeners = [
        'organization-created' => 'refreshOrganizations',
        'organization-updated' => 'refreshOrganizations',
        'organization-deleted' => 'handleOrganizationDeleted',
    ];

    public function mount($user = null)
    {
        // Use passed user or fallback to Auth::user()
        $this->user = $user ?? Auth::user();

        // Load current organization if user has one set
        if ($this->user && $this->user->organization_id) {
            $this->currentOrganization = Organization::find($this->user->organization_id);
        }

        $this->loadOrganizations();
    }

    public function loadOrganizations()
    {
        if (! $this->user) {
            $this->organizations = [];

            return;
        }

        // Get organizations where user is owner or member
        $ownedOrganizations = Organization::where('owner_id', $this->user->id)->get();

        // Get organizations where user is a member (if the relationship exists)
        $memberOrganizations = collect([]);
        if (method_exists($this->user, 'organizations')) {
            $memberOrganizations = $this->user->organizations;
        }

        $this->organizations = $ownedOrganizations->merge($memberOrganizations)->unique('id')->all();
    }

    public function switchOrganization($organizationId)
    {
        $this->errorMessage = null;

        try {
            $organization = Organization::find($organizationId);

            if (! $organization) {
                $this->errorMessage = __('Organization not found.');

                return;
            }

            // Check if user has access to this organization
            if (! $organization->isOwnedBy($this->user) && ! $organization->hasActiveMember($this->user)) {
                $this->errorMessage = __('You do not have access to this organization.');

                return;
            }

            // Update user's organization - cast to Model for save/refresh methods
            if ($this->user instanceof Model) {
                $this->user->organization_id = $organization->id;
                $this->user->save();
                $this->user->refresh();

                // Force refresh the authenticated user in session
                Auth::setUser($this->user);
            }

            $this->currentOrganization = $organization;
            $this->showDropdown = false;

            // Emit event for other components to listen to
            $this->dispatch('organization-switched', organizationId: $organization->id);
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to switch organization: '.$e->getMessage();
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
    public function handleOrganizationDeleted($organizationId)
    {
        // If the deleted organization was the current one, clear it
        if ($this->currentOrganization && $this->currentOrganization->id == $organizationId) {
            $this->currentOrganization = null;

            if ($this->user instanceof Model) {
                $this->user->update(['organization_id' => null]);
            }
        }

        // Refresh the organizations list
        $this->loadOrganizations();
    }

    public function render()
    {
        return view('org::livewire.organization-switcher');
    }
}
