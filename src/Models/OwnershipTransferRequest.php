<?php

namespace CleaniqueCoders\LaravelOrganization\Models;

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
 * @property int $current_owner_id
 * @property int $new_owner_id
 * @property string $token
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read User $currentOwner
 * @property-read User $newOwner
 */
class OwnershipTransferRequest extends Model
{
    use HasFactory;
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
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'organization_id',
        'current_owner_id',
        'new_owner_id',
        'token',
        'message',
        'accepted_at',
        'declined_at',
        'cancelled_at',
        'expires_at',
    ];

    /**
     * Create a new Eloquent model instance.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('organization.tables.ownership_transfer_requests', 'organization_ownership_transfer_requests');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organization for this transfer request.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(config('organization.organization-model'));
    }

    /**
     * Get the current owner (who initiated the transfer).
     */
    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(config('organization.user-model'), 'current_owner_id');
    }

    /**
     * Get the new owner (who will receive ownership).
     */
    public function newOwner(): BelongsTo
    {
        return $this->belongsTo(config('organization.user-model'), 'new_owner_id');
    }

    /**
     * Check if the request is pending (not accepted, declined, or cancelled).
     */
    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->declined_at === null
            && $this->cancelled_at === null;
    }

    /**
     * Check if the request has been accepted.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Check if the request has been declined.
     */
    public function isDeclined(): bool
    {
        return $this->declined_at !== null;
    }

    /**
     * Check if the request has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Check if the request has expired.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if the request is still valid (pending and not expired).
     */
    public function isValid(): bool
    {
        return $this->isPending() && ! $this->isExpired();
    }

    /**
     * Accept the transfer request.
     */
    public function accept(): self
    {
        $this->update(['accepted_at' => now()]);

        return $this;
    }

    /**
     * Decline the transfer request.
     */
    public function decline(): self
    {
        $this->update(['declined_at' => now()]);

        return $this;
    }

    /**
     * Cancel the transfer request (by current owner).
     */
    public function cancel(): self
    {
        $this->update(['cancelled_at' => now()]);

        return $this;
    }

    /**
     * Get the request's unique identifier.
     */
    public function getId(): int|string
    {
        return $this->getKey();
    }

    /**
     * Get the request's UUID.
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * Get the request's token.
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
     * Get the current owner ID.
     */
    public function getCurrentOwnerId(): int
    {
        return $this->current_owner_id;
    }

    /**
     * Get the new owner ID.
     */
    public function getNewOwnerId(): int
    {
        return $this->new_owner_id;
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get requests for a specific organization.
     */
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to get requests for a specific new owner.
     */
    public function scopeForNewOwner($query, int $userId)
    {
        return $query->where('new_owner_id', $userId);
    }
}
