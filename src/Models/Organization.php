<?php

namespace CleaniqueCoders\LaravelOrganization\Models;

use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithOrganizationSettings;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Events\MemberAdded;
use CleaniqueCoders\LaravelOrganization\Events\MemberRemoved;
use CleaniqueCoders\LaravelOrganization\Events\MemberRoleChanged;
use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferred;
use CleaniqueCoders\Traitify\Concerns\InteractsWithSlug;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

/**
 * @property int $id
 * @property string $uuid
 * @property int $owner_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<string, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Foundation\Auth\User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Foundation\Auth\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Foundation\Auth\User> $activeUsers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Foundation\Auth\User> $administrators
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Foundation\Auth\User> $members
 */
class Organization extends Model implements OrganizationContract, OrganizationMembershipContract, OrganizationOwnershipContract, OrganizationSettingsContract
{
    use HasFactory;
    use InteractsWithOrganizationSettings;
    use InteractsWithSlug;
    use InteractsWithUuid;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'settings',
        'owner_id',
    ];

    /**
     * Create a new Eloquent model instance.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('organization.tables.organizations', 'organizations');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // We'll handle validation in the save method instead of events
        // to avoid potential conflicts with multiple trait boot methods
    }

    /**
     * Override save to apply defaults and validate.
     */
    public function save(array $options = []): bool
    {
        // Apply defaults for new models
        if (! $this->exists) {
            $this->applyDefaultSettings();
        }

        // Always validate before saving
        $this->validateSettings();

        return parent::save($options);
    }

    /**
     * Get the owner of the organization.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('organization.user-model'), 'owner_id');
    }

    /**
     * Get all users (members and customers) of the organization.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('organization.user-model'),
            config('organization.tables.organization_users', 'organization_users')
        )
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get only active users of the organization.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Get owners of the organization (from pivot table).
     */
    public function owners(): BelongsToMany
    {
        return $this->activeUsers()->wherePivot('role', OrganizationRole::OWNER->value);
    }

    /**
     * Get administrators of the organization.
     */
    public function administrators(): BelongsToMany
    {
        return $this->activeUsers()->wherePivot('role', OrganizationRole::ADMINISTRATOR->value);
    }

    /**
     * Get members of the organization.
     */
    public function members(): BelongsToMany
    {
        return $this->activeUsers()->wherePivot('role', OrganizationRole::MEMBER->value);
    }

    /**
     * Get all members (users of all types) of the organization.
     */
    public function allMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeUsers()->get();
    }

    /**
     * Check if user is the owner of the organization.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Check if user is a member of the organization.
     */
    public function hasMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is an active member of the organization.
     */
    public function hasActiveMember(User $user): bool
    {
        return $this->activeUsers()->where('user_id', $user->id)->exists();
    }

    /**
     * Add a user to the organization with a specific role.
     */
    public function addUser(User $user, OrganizationRole $role = OrganizationRole::MEMBER, bool $isActive = true): void
    {
        $this->users()->attach($user->id, [
            'role' => $role->value,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dispatch the MemberAdded event
        MemberAdded::dispatch($this, $user, $role->value);
    }

    /**
     * Remove a user from the organization.
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);

        // Dispatch the MemberRemoved event
        MemberRemoved::dispatch($this, $user);
    }

    /**
     * Update user's role in the organization.
     */
    public function updateUserRole(User $user, OrganizationRole $role): void
    {
        // Get the old role before updating
        $oldRole = $this->getUserRole($user);

        $this->users()->updateExistingPivot($user->id, [
            'role' => $role->value,
            'updated_at' => now(),
        ]);

        // Only dispatch event if role actually changed
        if ($oldRole && $oldRole !== $role) {
            MemberRoleChanged::dispatch($this, $user, $oldRole->value, $role->value);
        }
    }

    /**
     * Activate or deactivate a user in the organization.
     */
    public function setUserActiveStatus(User $user, bool $isActive): void
    {
        $this->users()->updateExistingPivot($user->id, [
            'is_active' => $isActive,
            'updated_at' => now(),
        ]);
    }

    /**
     * Get user's role in the organization.
     */
    public function getUserRole(User $user): ?OrganizationRole
    {
        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;

        if (! $pivot) {
            return null;
        }

        $role = $pivot->getAttribute('role');

        return $role ? OrganizationRole::from($role) : null;
    }

    /**
     * Check if user has a specific role in the organization.
     */
    public function userHasRole(User $user, OrganizationRole $role): bool
    {
        return $this->getUserRole($user) === $role;
    }

    // Implementation of OrganizationContract methods

    /**
     * Get the organization's unique identifier.
     */
    public function getId(): int|string
    {
        return $this->getKey();
    }

    /**
     * Get the organization's UUID.
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Get the organization's name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the organization's slug.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Get the organization's description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Check if the organization is active (not soft deleted).
     */
    public function isActive(): bool
    {
        return $this->deleted_at === null;
    }

    // Implementation of OrganizationOwnershipContract methods

    /**
     * Get the owner ID.
     */
    public function getOwnerId(): int|string|null
    {
        return $this->owner_id;
    }

    /**
     * Set the owner of the organization.
     */
    public function setOwner(User $user): void
    {
        $this->owner_id = $user->id;
    }

    /**
     * Transfer ownership to another user.
     */
    public function transferOwnership(User $newOwner): void
    {
        $previousOwner = $this->owner;

        $this->setOwner($newOwner);
        $this->save();

        // Update pivot table roles
        // Previous owner becomes administrator
        if ($this->hasMember($previousOwner)) {
            $this->updateUserRole($previousOwner, OrganizationRole::ADMINISTRATOR);
        } else {
            $this->addUser($previousOwner, OrganizationRole::ADMINISTRATOR);
        }

        // New owner gets owner role
        if ($this->hasMember($newOwner)) {
            $this->updateUserRole($newOwner, OrganizationRole::OWNER);
        } else {
            $this->addUser($newOwner, OrganizationRole::OWNER);
        }

        // Dispatch the OwnershipTransferred event
        OwnershipTransferred::dispatch($this, $previousOwner, $newOwner);
    }

    // Implementation of OrganizationSettingsContract methods

    /**
     * Get all settings as array.
     */
    public function getAllSettings(): array
    {
        return $this->settings ?? [];
    }
}
