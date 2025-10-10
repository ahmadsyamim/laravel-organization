<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrganizationSwitcher extends Component
{
    public ?Organization $currentOrganization = null;

    public $organizations = [];

    public bool $showDropdown = false;

    public function mount()
    {
        $user = Auth::user();

        if ($user && property_exists($user, 'organization_id')) {
            $this->currentOrganization = $user->currentOrganization ?? Organization::find($user->organization_id);
        }

        $this->loadOrganizations();
    }

    public function loadOrganizations()
    {
        $user = Auth::user();

        if (! $user) {
            $this->organizations = collect([]);

            return;
        }

        // Get organizations where user is owner or member
        $ownedOrganizations = Organization::where('owner_id', $user->id)->get();

        // Get organizations where user is a member (if the relationship exists)
        $memberOrganizations = collect([]);
        if (method_exists($user, 'organizations')) {
            $memberOrganizations = $user->organizations;
        }

        $this->organizations = $ownedOrganizations->merge($memberOrganizations)->unique('id');
    }

    public function switchOrganization($organizationId)
    {
        $organization = Organization::find($organizationId);

        if (! $organization) {
            session()->flash('error', 'Organization not found.');

            return;
        }

        $user = Auth::user();

        // Check if user has access to this organization
        if (! $organization->isOwnedBy($user) && ! $organization->hasActiveMember($user)) {
            session()->flash('error', 'You do not have access to this organization.');

            return;
        }

        // Update user's current organization
        if (method_exists($user, 'update') && $user->getTable()) {
            $user->update(['organization_id' => $organization->id]);
        }

        $this->currentOrganization = $organization;
        $this->showDropdown = false;

        // Emit event for other components to listen to
        $this->dispatch('organization-switched', organizationId: $organization->id);

        // Show success message
        session()->flash('message', "Switched to {$organization->name}");

        // Optionally redirect to refresh the page
        return redirect()->to(request()->url());
    }

    public function toggleDropdown()
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function closeDropdown()
    {
        $this->showDropdown = false;
    }

    public function render()
    {
        return view('laravel-organization::livewire.organization-switcher');
    }
}
