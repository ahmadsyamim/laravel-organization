<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Actions\CancelOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Actions\InitiateOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class TransferOwnership extends Component
{
    public ?int $organizationId = null;

    public bool $showModal = false;

    public string $email = '';

    public string $message = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    protected function rules()
    {
        return [
            'email' => [
                'required',
                'email',
                'exists:users,email',
            ],
            'message' => 'nullable|string|max:1000',
        ];
    }

    protected $validationAttributes = [
        'email' => 'email address',
        'message' => 'message',
    ];

    public function mount(?int $organizationId = null)
    {
        $this->organizationId = $organizationId;
    }

    public function showModal()
    {
        $this->showModal = true;
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->resetValidation();
    }

    #[On('show-transfer-ownership')]
    public function showTransferModal($organizationId = null)
    {
        if ($organizationId) {
            $this->organizationId = $organizationId;
        }
        $this->showModal();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['email', 'message', 'errorMessage', 'successMessage']);
        $this->resetValidation();
    }

    public function updatedEmail()
    {
        $this->validateOnly('email');
    }

    public function initiateTransfer()
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = 'Please correct the validation errors below.';
            throw $e;
        }

        $user = Auth::user();

        if (! $user) {
            $this->errorMessage = 'You must be logged in to transfer ownership.';

            return;
        }

        $organization = $this->getOrganization();

        if (! $organization) {
            $this->errorMessage = 'Organization not found.';

            return;
        }

        // Check if user is the owner
        if (! $organization->isOwnedBy($user)) {
            $this->errorMessage = 'Only the owner can transfer ownership.';

            return;
        }

        // Find the new owner by email
        $newOwner = config('organization.user-model')::where('email', $this->email)->first();

        if (! $newOwner) {
            $this->errorMessage = 'User not found with the provided email.';

            return;
        }

        // Check if trying to transfer to self
        if ($user->id === $newOwner->id) {
            $this->errorMessage = 'You cannot transfer ownership to yourself.';

            return;
        }

        try {
            InitiateOwnershipTransfer::run(
                $organization,
                $user,
                $newOwner,
                $this->message ?: null
            );

            $this->successMessage = "Ownership transfer request has been sent to {$newOwner->email}. They will receive an email to accept or decline the transfer.";
            $this->reset(['email', 'message']);

            // Emit events
            $this->dispatch('transfer-initiated');

        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid argument for ownership transfer', [
                'organization_id' => $organization->id,
                'user_id' => $user->getAuthIdentifier(),
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = $e->getMessage();
        } catch (QueryException $e) {
            Log::error('Database error during ownership transfer', [
                'organization_id' => $organization->id,
                'user_id' => $user->getAuthIdentifier(),
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = __('Database error occurred. Please try again or contact support.');
        } catch (\Throwable $e) {
            Log::error('Failed to initiate ownership transfer', [
                'organization_id' => $organization->id,
                'user_id' => $user->getAuthIdentifier(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = __('Failed to initiate ownership transfer. Please try again.');
        }
    }

    public function cancelPendingTransfer()
    {
        $user = Auth::user();

        if (! $user) {
            $this->errorMessage = 'You must be logged in to cancel the transfer.';

            return;
        }

        $organization = $this->getOrganization();

        if (! $organization) {
            $this->errorMessage = 'Organization not found.';

            return;
        }

        $pendingRequest = $this->getPendingTransferRequest();

        if (! $pendingRequest) {
            $this->errorMessage = 'No pending transfer request found.';

            return;
        }

        try {
            CancelOwnershipTransfer::run($pendingRequest, $user);
            $this->successMessage = 'Transfer request has been cancelled.';
            $this->dispatch('transfer-cancelled');

        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Throwable $e) {
            Log::error('Failed to cancel ownership transfer', [
                'request_id' => $pendingRequest->id,
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = __('Failed to cancel transfer request. Please try again.');
        }
    }

    public function getOrganization(): ?Organization
    {
        if (! $this->organizationId) {
            return null;
        }

        return Organization::find($this->organizationId);
    }

    public function getPendingTransferRequest(): ?OwnershipTransferRequest
    {
        if (! $this->organizationId) {
            return null;
        }

        return OwnershipTransferRequest::pending()
            ->forOrganization($this->organizationId)
            ->first();
    }

    public function render()
    {
        return view('org::livewire.transfer-ownership', [
            'organization' => $this->getOrganization(),
            'pendingRequest' => $this->getPendingTransferRequest(),
        ]);
    }
}
