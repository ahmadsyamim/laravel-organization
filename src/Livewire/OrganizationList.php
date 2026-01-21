<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\LaravelOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property \Illuminate\Contracts\Pagination\LengthAwarePaginator $organizations
 */
class OrganizationList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public string $filter = 'all'; // 'all', 'owned', 'member'

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'filter' => ['except' => 'all'],
    ];

    #[On('organization-created')]
    #[On('organization-updated')]
    #[On('organization-deleted')]
    public function refresh(): void
    {
        // This method triggers a re-render of the component
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilter()
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
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

    public function editOrganization(int $organizationId): void
    {
        $this->dispatch('show-manage-organization', [
            'organizationId' => $organizationId,
            'mode' => 'edit',
        ]);
    }

    public function deleteOrganization(int $organizationId): void
    {
        $this->dispatch('show-manage-organization', [
            'organizationId' => $organizationId,
            'mode' => 'delete',
        ]);
    }

    /**
     * Switch to a different organization (session-based, no DB write).
     */
    public function switchToOrganization(int $organizationId): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $organization = Organization::find($organizationId);

            if (! $organization) {
                $this->errorMessage = 'Organization not found.';

                return;
            }

            $user = Auth::user();

            // Check if user has access to this organization
            if (! $organization->isOwnedBy($user) && ! $organization->hasActiveMember($user)) {
                $this->errorMessage = 'You do not have access to this organization.';

                return;
            }

            // Store in session only (no DB write)
            LaravelOrganization::setCurrentOrganizationId($organization->id);

            // Emit event for other components to listen to
            $this->dispatch('organization-switched', organizationId: $organization->id);
        } catch (ModelNotFoundException $e) {
            Log::warning('Organization not found during switch', [
                'organization_id' => $organizationId,
                'user_id' => Auth::id(),
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
     * Set an organization as the user's default (persisted to DB).
     */
    public function setAsDefault(int $organizationId): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $organization = Organization::find($organizationId);

            if (! $organization) {
                $this->errorMessage = __('Organization not found.');

                return;
            }

            $user = Auth::user();

            // Check if user has access to this organization
            if (! $organization->isOwnedBy($user) && ! $organization->hasActiveMember($user)) {
                $this->errorMessage = __('You do not have access to this organization.');

                return;
            }

            $user->setAttribute('organization_id', $organization->id);
            $user->save();

            // Update session to keep in sync
            LaravelOrganization::setCurrentOrganizationId($organization->id);

            $this->successMessage = __('Default organization updated.');

            $this->dispatch('default-organization-changed', organizationId: $organization->id);
        } catch (\Throwable $e) {
            Log::error('Failed to set default organization', [
                'organization_id' => $organizationId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = __('Failed to set default organization. Please try again.');
        }
    }

    /**
     * Check if an organization is the user's default.
     */
    public function isDefaultOrganization(int $organizationId): bool
    {
        $user = Auth::user();

        return $user && $user->organization_id === $organizationId;
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

        // Eager load the current user's membership to prevent N+1 queries
        $query->with(['users' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }]);

        return $query->paginate(10);
    }

    public function getUserRoleInOrganization($organization): string
    {
        $user = Auth::user();

        if ($organization->isOwnedBy($user)) {
            return 'Owner';
        }

        // Use eager-loaded users relationship to avoid N+1 queries
        $membership = $organization->relationLoaded('users')
            ? $organization->users->first()
            : $organization->users()->where('user_id', $user->id)->first();

        if ($membership && $membership->pivot) {
            $roleValue = $membership->pivot->role;
            $role = OrganizationRole::tryFrom($roleValue);

            return $role ? $role->label() : OrganizationRole::MEMBER->label();
        }

        return OrganizationRole::MEMBER->label();
    }

    public function render()
    {
        return view('org::livewire.organization-list', [
            'organizations' => $this->organizations,
        ]);
    }
}
