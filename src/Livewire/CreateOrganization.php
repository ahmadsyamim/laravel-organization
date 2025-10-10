<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateOrganization extends Component
{
    public bool $showModal = false;

    public string $name = '';

    public string $description = '';

    public bool $setAsCurrent = false;

    public ?string $errorMessage = null;

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('organizations', 'name')->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string|max:1000',
            'setAsCurrent' => 'boolean',
        ];
    }

    protected $validationAttributes = [
        'name' => 'organization name',
        'description' => 'organization description',
    ];

    protected $listeners = [
        'show-create-organization' => 'showModal',
    ];

    public function showModal()
    {
        $this->showModal = true;
        $this->errorMessage = null;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['name', 'description', 'setAsCurrent', 'errorMessage']);
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

    public function createOrganization()
    {
        $this->errorMessage = null;

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = 'Please correct the validation errors below.';
            throw $e;
        }

        $user = Auth::user();

        if (! $user) {
            $this->errorMessage = 'You must be logged in to create an organization.';

            return;
        }

        try {
            // Create organization using the action
            // Pass setAsCurrent as the 'default' parameter to determine if this should be the user's default org
            $organization = CreateNewOrganization::run(
                $user,
                $this->setAsCurrent, // Whether this is the default organization
                $this->name,
                $this->description ?: null
            );

            // Reset form
            $this->reset(['name', 'description', 'setAsCurrent', 'errorMessage']);
            $this->showModal = false;

            // Emit events
            $this->dispatch('organization-created', organizationId: $organization->id);
            $this->dispatch('organization-switched', organizationId: $organization->id);

            session()->flash('message', "Organization '{$organization->name}' created successfully!");

        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to create organization: '.$e->getMessage();
            Log::error('Failed to create organization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function render()
    {
        return view('org::livewire.create-organization-form');
    }
}
