<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrganizationWidget extends Component
{
    public ?Organization $currentOrganization = null;

    public $organizations = [];

    public $recentOrganizations = [];

    public bool $showQuickActions = true;

    protected $listeners = [
        'organization-switched' => 'refreshWidget',
        'organization-created' => 'refreshWidget',
        'organization-updated' => 'refreshWidget',
        'organization-deleted' => 'refreshWidget',
    ];

    public function mount($showQuickActions = true)
    {
        $this->showQuickActions = $showQuickActions;
        $this->loadCurrentOrganization();
        $this->loadOrganizations();
    }

    public function refreshWidget()
    {
        $this->loadCurrentOrganization();
        $this->loadOrganizations();
    }

    public function loadCurrentOrganization()
    {
        $user = Auth::user();

        if ($user && property_exists($user, 'organization_id')) {
            $this->currentOrganization = $user->currentOrganization ?? Organization::find($user->organization_id);
        }
    }

    public function loadOrganizations()
    {
        $user = Auth::user();

        if (! $user) {
            $this->organizations = collect([]);
            $this->recentOrganizations = collect([]);

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

        // Get recent organizations (last 5)
        $this->recentOrganizations = $this->organizations->sortByDesc('updated_at')->take(5);
    }

    public function switchToOrganization($organizationId)
    {
        $this->dispatch('organization-switch-requested', organizationId: $organizationId);
    }

    public function showCreateForm()
    {
        $this->dispatch('show-create-organization');
    }

    public function showManageForm($organizationId = null)
    {
        $targetId = $organizationId ?? ($this->currentOrganization?->id);

        if ($targetId) {
            $this->dispatch('show-manage-organization', [
                'organizationId' => $targetId,
                'mode' => 'edit',
            ]);
        }
    }

    public function showOrganizationList()
    {
        $this->dispatch('show-organization-list');
    }

    public function render()
    {
        return view('laravel-organization::livewire.organization-widget');
    }
}
