<?php

namespace CleaniqueCoders\LaravelOrganization\Models;

use App\Enums\OrganizationRole;
use CleaniqueCoders\Traitify\Concerns\InteractsWithSlug;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory;
    use InteractsWithSlug;
    use InteractsWithUuid;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'settings',
        'owner_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'array',
        'deleted_at' => 'datetime',
    ];

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
        return $this->belongsToMany(config('organization.user-model'), 'organization_users')
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
     * Get customers of the organization.
     */
    public function customers(): BelongsToMany
    {
        return $this->activeUsers()->wherePivot('role', OrganizationRole::CUSTOMER->value);
    }

    /**
     * Get all teams of the organization.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get all applications of the organization.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get all projects of the organization.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get all members (users of all types) of the organization.
     */
    public function allMembers()
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
    }

    /**
     * Remove a user from the organization.
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Update user's role in the organization.
     */
    public function updateUserRole(User $user, OrganizationRole $role): void
    {
        $this->users()->updateExistingPivot($user->id, [
            'role' => $role->value,
            'updated_at' => now(),
        ]);
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

        return $pivot?->role ? OrganizationRole::from($pivot->role) : null;
    }

    /**
     * Check if user has a specific role in the organization.
     */
    public function userHasRole(User $user, OrganizationRole $role): bool
    {
        return $this->getUserRole($user) === $role;
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }
}
