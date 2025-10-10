<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateOrganizationForm extends Component
{
    public bool $showModal = false;

    public string $name = '';

    public string $description = '';

    public bool $setAsCurrent = false;

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
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['name', 'description', 'setAsCurrent']);
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
        $this->validate();

        $user = Auth::user();

        if (! $user) {
            session()->flash('error', 'You must be logged in to create an organization.');

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
            $this->reset(['name', 'description', 'setAsCurrent']);
            $this->showModal = false;

            // Emit events
            $this->dispatch('organization-created', organizationId: $organization->id);
            $this->dispatch('organization-switched', organizationId: $organization->id);

            session()->flash('message', "Organization '{$organization->name}' created successfully!");

            // Refresh the page or redirect
            return redirect()->to(request()->url());

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create organization: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('laravel-organization::livewire.create-organization-form');
    }
}
