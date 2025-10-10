<?php

namespace CleaniqueCoders\LaravelOrganization\Actions;

use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateOrganization
{
    use AsAction;

    /**
     * Update an organization with validated data.
     *
     * @param  Organization  $organization  The organization to update
     * @param  User  $user  The user performing the update
     * @param  array  $data  The data to update (name, description)
     * @return Organization The updated organization
     *
     * @throws ValidationException If validation fails
     * @throws \Exception If user doesn't have permission
     */
    public function handle(Organization $organization, User $user, array $data): Organization
    {
        // Check if user has permission to update
        if (! $organization->isOwnedBy($user) && ! $this->isUserAdministrator($organization, $user)) {
            throw new \Exception('You do not have permission to update this organization.');
        }

        // Validate the data
        $validated = $this->validateData($organization, $data);

        // Update the organization
        $organization->update($validated);

        return $organization->fresh();
    }

    /**
     * Validate the organization update data.
     *
     * @param  Organization  $organization  The organization being updated
     * @param  array  $data  The data to validate
     * @return array The validated data
     *
     * @throws ValidationException
     */
    protected function validateData(Organization $organization, array $data): array
    {
        $validator = Validator::make($data, [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('organizations', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($organization->id),
            ],
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'The organization name is required.',
            'name.min' => 'The organization name must be at least 2 characters.',
            'name.max' => 'The organization name must not exceed 255 characters.',
            'name.unique' => 'An organization with this name already exists.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Check if user is an administrator of the organization.
     *
     * @param  Organization  $organization  The organization to check
     * @param  User  $user  The user to check
     */
    protected function isUserAdministrator(Organization $organization, User $user): bool
    {
        $userRole = $organization->getUserRole($user);

        return $userRole && $userRole->value === 'administrator';
    }

    /**
     * Get validation rules for updating an organization.
     *
     * @param  Organization  $organization  The organization being updated
     */
    public static function rules(Organization $organization): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('organizations', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($organization->id),
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get validation messages.
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'The organization name is required.',
            'name.min' => 'The organization name must be at least 2 characters.',
            'name.max' => 'The organization name must not exceed 255 characters.',
            'name.unique' => 'An organization with this name already exists.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ];
    }
}
