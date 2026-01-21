<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Actions\DeleteOrganization;
use CleaniqueCoders\LaravelOrganization\Actions\UpdateOrganization as UpdateAction;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class UpdateOrganization extends Component
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

    #[On('show-manage-organization')]
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

        try {
            // Use the UpdateOrganization action
            $updatedOrganization = UpdateAction::run(
                $this->organization,
                $user,
                [
                    'name' => $this->name,
                    'description' => $this->description,
                ]
            );

            $this->closeModal();

            // Emit events
            $this->dispatch('organization-updated', organizationId: $updatedOrganization->id);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid argument for organization update', [
                'organization_id' => $this->organization->id,
                'name' => $this->name,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            // Pass through business logic validation messages directly
            $this->errorMessage = $e->getMessage();
        } catch (QueryException $e) {
            Log::error('Database error during organization update', [
                'organization_id' => $this->organization->id,
                'name' => $this->name,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = __('Database error occurred. Please try again.');
        } catch (\Throwable $e) {
            Log::error('Failed to update organization', [
                'organization_id' => $this->organization->id,
                'name' => $this->name,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = __('Failed to update organization. Please try again.');
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

        try {
            // Use the DeleteOrganization action
            $result = DeleteOrganization::run($this->organization, $user);

            $this->closeModal();

            // Emit events
            $this->dispatch('organization-deleted', organizationId: $result['deleted_organization_id']);
        } catch (QueryException $e) {
            Log::error('Database error during organization deletion', [
                'organization_id' => $this->organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = __('Database error occurred. Please try again.');
        } catch (\Exception $e) {
            // Log business logic and other exceptions
            Log::info('Organization deletion validation or error', [
                'organization_id' => $this->organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            // Pass through business logic validation messages directly
            $this->errorMessage = $e->getMessage();
        } catch (\Throwable $e) {
            Log::error('Unexpected error during organization deletion', [
                'organization_id' => $this->organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = __('An unexpected error occurred. Please try again.');
        }
    }

    protected function isUserAdministrator(User $user): bool
    {
        if (! $this->organization) {
            return false;
        }

        $userRole = $this->organization->getUserRole($user);

        return $userRole && $userRole->isAdmin();
    }

    public function render()
    {
        return view('org::livewire.update-organization');
    }
}
