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

    public ?string $errorMessage = null;

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

        $this->errorMessage = null;

        if (! $organizationId) {
            $this->errorMessage = 'Organization ID is required.';

            return;
        }

        $this->organization = Organization::find($organizationId);

        if (! $this->organization) {
            $this->errorMessage = 'Organization not found.';

            return;
        }

        // Check if user has permission to manage this organization
        $user = Auth::user();
        if (! $this->organization->isOwnedBy($user) && ! $this->isUserAdministrator($user)) {
            $this->errorMessage = 'You do not have permission to manage this organization.';

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
        $this->reset(['name', 'description', 'confirmationName', 'mode', 'errorMessage']);
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
        $this->errorMessage = null;

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = 'Please correct the validation errors below.';
            throw $e;
        }

        if (! $this->organization) {
            $this->errorMessage = 'Organization not found.';

            return;
        }

        $user = Auth::user();
        if (! $this->organization->isOwnedBy($user) && ! $this->isUserAdministrator($user)) {
            $this->errorMessage = 'You do not have permission to update this organization.';

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
            $this->errorMessage = 'Failed to update organization: '.$e->getMessage();
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
        $this->errorMessage = null;

        if (! $this->organization) {
            $this->errorMessage = 'Organization not found.';

            return;
        }

        // Validate confirmation name
        if ($this->confirmationName !== $this->organization->name) {
            $this->addError('confirmationName', 'Organization name does not match.');
            $this->errorMessage = 'Organization name does not match.';

            return;
        }

        $user = Auth::user();
        if (! $this->organization->isOwnedBy($user)) {
            $this->errorMessage = 'Only the organization owner can delete the organization.';

            return;
        }

        // Check if user only has one organization
        $userOrganizationCount = Organization::where('owner_id', $user->id)->count();

        if ($userOrganizationCount <= 1) {
            $this->errorMessage = 'Cannot delete your only organization. You must have at least one organization.';

            return;
        }

        // Check if this is the user's current organization
        if (property_exists($user, 'organization_id') &&
            $user->organization_id === $this->organization->id) {
            $this->errorMessage = 'Cannot delete your current organization. Please switch to another organization first.';

            return;
        }

        // Check if organization has active members (excluding owner)
        $activeMembersCount = $this->organization->activeUsers()
            ->where('user_id', '!=', $user->id)
            ->count();

        if ($activeMembersCount > 0) {
            $this->errorMessage = 'Cannot delete organization with active members. Remove all members first.';

            return;
        }

        try {
            $organizationName = $this->organization->name;

            // Permanently delete the organization (force delete)
            $this->organization->forceDelete();

            $this->closeModal();

            // Emit events
            $this->dispatch('organization-deleted', organizationId: $this->organization->id);

            session()->flash('message', "Organization '{$organizationName}' has been permanently deleted!");

            // Refresh the page
            return redirect()->to(request()->url());

        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to delete organization: '.$e->getMessage();
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
