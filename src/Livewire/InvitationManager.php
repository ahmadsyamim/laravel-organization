<?php

namespace CleaniqueCoders\LaravelOrganization\Livewire;

use CleaniqueCoders\LaravelOrganization\Actions\AcceptInvitation;
use CleaniqueCoders\LaravelOrganization\Actions\DeclineInvitation;
use CleaniqueCoders\LaravelOrganization\Actions\ResendInvitation;
use CleaniqueCoders\LaravelOrganization\Actions\SendInvitation;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Foundation\Auth\User;
use Livewire\Component;
use Livewire\WithPagination;

class InvitationManager extends Component
{
    use WithPagination;

    public Organization $organization;

    public User $currentUser;

    public string $email = '';

    public string $role = 'member';

    public bool $showSendForm = false;

    /**
     * Validation rules for sending invitations.
     */
    protected $rules = [
        'email' => 'required|email|max:255',
        'role' => 'required|in:member,administrator',
    ];

    /**
     * Mount the component with organization context.
     */
    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->currentUser = auth()->user();
        $this->role = OrganizationRole::MEMBER->value;
    }

    /**
     * Send an invitation to join the organization.
     */
    public function sendInvitation(): void
    {
        $this->validate();

        try {
            $role = OrganizationRole::from($this->role);

            (new SendInvitation)->handle(
                $this->organization,
                $this->currentUser,
                $this->email,
                $role
            );

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Invitation sent to {$this->email}",
            ]);

            $this->reset('email', 'role', 'showSendForm');
            $this->resetPage();
        } catch (\InvalidArgumentException $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    /**
     * Resend an invitation.
     */
    public function resendInvitation(Invitation $invitation): void
    {
        try {
            (new ResendInvitation)->handle($invitation);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "Invitation resent to {$invitation->email}",
            ]);

            $this->resetPage();
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Accept an invitation for the current user.
     */
    public function acceptInvitation(Invitation $invitation): void
    {
        try {
            (new AcceptInvitation)->handle($invitation, $this->currentUser);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => "You've successfully joined {$this->organization->name}!",
            ]);

            $this->resetPage();
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Decline an invitation.
     */
    public function declineInvitation(Invitation $invitation): void
    {
        try {
            (new DeclineInvitation)->handle($invitation);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Invitation declined',
            ]);

            $this->resetPage();
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get paginated pending invitations for the organization.
     */
    public function getPendingInvitationsProperty()
    {
        return Invitation::query()
            ->where('organization_id', $this->organization->id)
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Get all pending invitations for the current user.
     */
    public function getUserPendingInvitationsProperty()
    {
        return Invitation::query()
            ->where('email', $this->currentUser->email)
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        return view('org::livewire.invitation-manager', [
            'pendingInvitations' => $this->pendingInvitations,
            'userPendingInvitations' => $this->userPendingInvitations,
            'roles' => [
                OrganizationRole::MEMBER->value => OrganizationRole::MEMBER->label(),
                OrganizationRole::ADMINISTRATOR->value => OrganizationRole::ADMINISTRATOR->label(),
            ],
        ]);
    }
}
