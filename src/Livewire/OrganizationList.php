<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public string $filter = 'all'; // 'all', 'owned', 'member'

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'filter' => ['except' => 'all'],
    ];

    protected $listeners = [
        'organization-created' => '$refresh',
        'organization-updated' => '$refresh',
        'organization-deleted' => '$refresh',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'sortBy', 'sortDirection', 'filter']);
        $this->resetPage();
    }

    public function editOrganization($organizationId)
    {
        $this->dispatch('show-manage-organization', [
            'organizationId' => $organizationId,
            'mode' => 'edit',
        ]);
    }

    public function deleteOrganization($organizationId)
    {
        $this->dispatch('show-manage-organization', [
            'organizationId' => $organizationId,
            'mode' => 'delete',
        ]);
    }

    public function switchToOrganization($organizationId)
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
        if (method_exists($user, 'update')) {
            $user->update(['organization_id' => $organization->id]);
        }

        // Emit event for other components to listen to
        $this->dispatch('organization-switched', organizationId: $organization->id);

        session()->flash('message', "Switched to {$organization->name}");

        // Refresh the page
        return redirect()->to(request()->url());
    }

    public function getOrganizationsProperty()
    {
        $user = Auth::user();

        if (! $user) {
            return collect([]);
        }

        $query = Organization::query();

        // Apply filter
        switch ($this->filter) {
            case 'owned':
                $query->where('owner_id', $user->id);
                break;
            case 'member':
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->where('is_active', true);
                })->where('owner_id', '!=', $user->id);
                break;
            case 'all':
            default:
                $query->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhereHas('users', function ($subQ) use ($user) {
                            $subQ->where('user_id', $user->id)
                                ->where('is_active', true);
                        });
                });
                break;
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(10);
    }

    public function getUserRoleInOrganization($organization)
    {
        $user = Auth::user();

        if ($organization->isOwnedBy($user)) {
            return 'Owner';
        }

        $role = $organization->getUserRole($user);

        return $role ? ucfirst($role->value) : 'Member';
    }

    public function render()
    {
        return view('laravel-organization::livewire.organization-list', [
            'organizations' => $this->organizations,
        ]);
    }
}
