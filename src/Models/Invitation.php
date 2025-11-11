<?php

namespace CleaniqueCoders\LaravelOrganization\Models;

use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int|null $invited_by_user_id
 * @property int|null $user_id
 * @property string $email
 * @property string $token
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read User|null $invitedUser
 * @property-read User|null $invitedByUser
 */
class Invitation extends Model
{
    use HasFactory;
    use InteractsWithUuid;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'organization_id',
        'invited_by_user_id',
        'user_id',
        'email',
        'token',
        'role',
        'accepted_at',
        'declined_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organization that this invitation belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who was invited (if they have an account).
     */
    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Check if the invitation is pending (not accepted or declined).
     */
    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->declined_at === null;
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Check if the invitation has been declined.
     */
    public function isDeclined(): bool
    {
        return $this->declined_at !== null;
    }

    /**
     * Check if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if the invitation is still valid (pending and not expired).
     */
    public function isValid(): bool
    {
        return $this->isPending() && ! $this->isExpired();
    }

    /**
     * Accept the invitation.
     */
    public function accept(User $user): self
    {
        $this->update([
            'user_id' => $user->id,
            'accepted_at' => now(),
        ]);

        return $this;
    }

    /**
     * Decline the invitation.
     */
    public function decline(): self
    {
        $this->update(['declined_at' => now()]);

        return $this;
    }

    /**
     * Get the role enum value.
     */
    public function getRoleEnum(): OrganizationRole
    {
        return OrganizationRole::from($this->role);
    }

    /**
     * Get the invitation's unique identifier.
     */
    public function getId()
    {
        return $this->getKey();
    }

    /**
     * Get the invitation's UUID.
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Get the invitation's email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the invitation's token.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get the organization ID.
     */
    public function getOrganizationId(): int
    {
        return $this->organization_id;
    }

    /**
     * Get the invited by user ID.
     */
    public function getInvitedByUserId(): ?int
    {
        return $this->invited_by_user_id;
    }

    /**
     * Get the invited user ID (if accepted).
     */
    public function getInvitedUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * Get the accepted at timestamp.
     */
    public function getAcceptedAt()
    {
        return $this->accepted_at;
    }

    /**
     * Get the declined at timestamp.
     */
    public function getDeclinedAt()
    {
        return $this->declined_at;
    }

    /**
     * Get the expires at timestamp.
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }
}
