<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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

    #[On('show-create-organization')]
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

        // Apply rate limiting: max 5 organizations per hour per user
        $rateLimitKey = 'create-organization:'.$user->getAuthIdentifier();
        $maxAttempts = config('organization.rate_limits.create_organization.max_attempts', 5);
        $decayMinutes = config('organization.rate_limits.create_organization.decay_minutes', 60);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);

            $this->errorMessage = __('Too many organization creation attempts. Please try again in :minutes minutes.', [
                'minutes' => $minutes,
            ]);

            Log::warning('Rate limit exceeded for organization creation', [
                'user_id' => $user->getAuthIdentifier(),
                'available_in_seconds' => $seconds,
            ]);

            return;
        }

        try {
            // Increment rate limiter
            RateLimiter::hit($rateLimitKey, $decayMinutes * 60);
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

        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid argument for organization creation', [
                'name' => $this->name,
                'user_id' => $user->getAuthIdentifier(),
                'error' => $e->getMessage(),
            ]);
            // Pass through business logic validation messages directly
            $this->errorMessage = $e->getMessage();
        } catch (QueryException $e) {
            Log::error('Database error during organization creation', [
                'name' => $this->name,
                'user_id' => $user->getAuthIdentifier(),
                'error' => $e->getMessage(),
            ]);
            $this->errorMessage = __('Database error occurred. Please try again or contact support.');
        } catch (\Throwable $e) {
            Log::error('Failed to create organization', [
                'name' => $this->name,
                'user_id' => $user->getAuthIdentifier(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->errorMessage = __('Failed to create organization. Please try again.');
        }
    }

    public function render()
    {
        return view('org::livewire.create-organization-form');
    }
}
