<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManageOrganization extends Component
{
    public ?Organization $organization = null;

    public bool $showModal = false;

    public string $mode = 'edit'; // 'edit' or 'delete'

    // Form fields
    public string $name = '';

    public string $description = '';

    public string $confirmationName = '';

    // UI states
    public bool $showDeleteConfirmation = false;

    protected function rules()
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'description' => 'nullable|string|max:1000',
        ];

        // Add unique validation for name, excluding current organization
        if ($this->organization) {
            $rules['name'][] = Rule::unique('organizations', 'name')
                ->whereNull('deleted_at')
                ->ignore($this->organization->id);
        }

        return $rules;
    }

    protected $validationAttributes = [
        'name' => 'organization name',
        'description' => 'organization description',
        'confirmationName' => 'confirmation name',
    ];

    protected $listeners = [
        'show-manage-organization' => 'showManageModal',
    ];

    public function showManageModal($data)
    {
        $organizationId = $data['organizationId'] ?? null;
        $mode = $data['mode'] ?? 'edit';

        if (! $organizationId) {
            session()->flash('error', 'Organization ID is required.');

            return;
        }

        $this->organization = Organization::find($organizationId);

        if (! $this->organization) {
            session()->flash('error', 'Organization not found.');

            return;
        }

        // Check if user has permission to manage this organization
        $user = Auth::user();
        if (! $this->organization->isOwnedBy($user) && ! $this->isUserAdministrator($user)) {
            session()->flash('error', 'You do not have permission to manage this organization.');

            return;
        }

        $this->mode = $mode;
        $this->loadOrganizationData();
        $this->showModal = true;
        $this->resetValidation();
    }

    public function loadOrganizationData()
    {
        if ($this->organization) {
            $this->name = $this->organization->name;
            $this->description = $this->organization->description ?? '';
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->showDeleteConfirmation = false;
        $this->reset(['name', 'description', 'confirmationName', 'mode']);
        $this->organization = null;
        $this->resetValidation();
    }

    public function updatedName()
    {
        $this->validateOnly('name');
    }

    public function updatedDescription()
    {
        $this->validateOnly('description');
    }

    public function updateOrganization()
    {
        $this->validate();

        if (! $this->organization) {
            session()->flash('error', 'Organization not found.');

            return;
        }

        $user = Auth::user();
        if (! $this->organization->isOwnedBy($user) && ! $this->isUserAdministrator($user)) {
            session()->flash('error', 'You do not have permission to update this organization.');

            return;
        }

        try {
            $this->organization->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->closeModal();

            // Emit events
            $this->dispatch('organization-updated', organizationId: $this->organization->id);

            session()->flash('message', "Organization '{$this->organization->name}' updated successfully!");

            // Refresh the page
            return redirect()->to(request()->url());

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update organization: '.$e->getMessage());
        }
    }

    public function confirmDelete()
    {
        $this->showDeleteConfirmation = true;
        $this->confirmationName = '';
    }

    public function cancelDelete()
    {
        $this->showDeleteConfirmation = false;
        $this->confirmationName = '';
    }

    public function deleteOrganization()
    {
        if (! $this->organization) {
            session()->flash('error', 'Organization not found.');

            return;
        }

        // Validate confirmation name
        if ($this->confirmationName !== $this->organization->name) {
            $this->addError('confirmationName', 'Organization name does not match.');

            return;
        }

        $user = Auth::user();
        if (! $this->organization->isOwnedBy($user)) {
            session()->flash('error', 'Only the organization owner can delete the organization.');

            return;
        }

        // Check if organization has active members (excluding owner)
        $activeMembersCount = $this->organization->activeUsers()
            ->where('user_id', '!=', $user->id)
            ->count();

        if ($activeMembersCount > 0) {
            session()->flash('error', 'Cannot delete organization with active members. Remove all members first.');

            return;
        }

        try {
            $organizationName = $this->organization->name;

            // If this is the user's current organization, clear it
            if (property_exists($user, 'organization_id') &&
                $user->organization_id === $this->organization->id) {
                $user->update(['organization_id' => null]);
            }

            // Soft delete the organization
            $this->organization->delete();

            $this->closeModal();

            // Emit events
            $this->dispatch('organization-deleted', organizationId: $this->organization->id);
            $this->dispatch('organization-switched', organizationId: null);

            session()->flash('message', "Organization '{$organizationName}' deleted successfully!");

            // Refresh the page
            return redirect()->to(request()->url());

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete organization: '.$e->getMessage());
        }
    }

    protected function isUserAdministrator(User $user): bool
    {
        if (! $this->organization) {
            return false;
        }

        $userRole = $this->organization->getUserRole($user);

        return $userRole && $userRole->value === 'administrator';
    }

    public function render()
    {
        return view('laravel-organization::livewire.manage-organization');
    }
}
