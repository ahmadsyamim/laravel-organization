<?php

namespace CleaniqueCoders\LaravelOrganization\Tests;

use CleaniqueCoders\LaravelOrganization\Actions\AcceptInvitation;
use CleaniqueCoders\LaravelOrganization\Actions\DeclineInvitation;
use CleaniqueCoders\LaravelOrganization\Actions\ResendInvitation;
use CleaniqueCoders\LaravelOrganization\Actions\SendInvitation;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Events\InvitationAccepted;
use CleaniqueCoders\LaravelOrganization\Events\InvitationDeclined;
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Support\Facades\Event;

describe('Invitation System', function () {
    beforeEach(function () {
        Event::fake();
    });

    describe('SendInvitation Action', function () {
        it('can send an invitation', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'john@example.com',
                OrganizationRole::MEMBER
            );

            expect($invitation)->toBeInstanceOf(Invitation::class)
                ->and($invitation->organization_id)->toBe($organization->id)
                ->and($invitation->invited_by_user_id)->toBe($invitedBy->id)
                ->and($invitation->email)->toBe('john@example.com')
                ->and($invitation->role)->toBe(OrganizationRole::MEMBER->value)
                ->and($invitation->isPending())->toBeTrue()
                ->and($invitation->token)->toBeTruthy();

            Event::assertDispatched(InvitationSent::class, function (InvitationSent $event) use ($invitation) {
                return $event->invitation->is($invitation);
            });
        });

        it('can send an invitation with admin role', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'admin@example.com',
                OrganizationRole::ADMINISTRATOR
            );

            expect($invitation->role)->toBe(OrganizationRole::ADMINISTRATOR->value);
        });

        it('generates unique tokens for invitations', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            $invitation1 = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'john1@example.com'
            );

            $invitation2 = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'john2@example.com'
            );

            expect($invitation1->token)->not->toBe($invitation2->token);
        });

        it('normalizes email to lowercase', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'JOHN@EXAMPLE.COM'
            );

            expect($invitation->email)->toBe('john@example.com');
        });

        it('throws exception for invalid email', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'invalid-email'
            );
        })->throws(\InvalidArgumentException::class, 'Invalid email address');

        it('throws exception if user is already a member', function () {
            $organization = Organization::factory()->create();
            $user = UserFactory::new()->create();
            $invitedBy = UserFactory::new()->create();

            $organization->addUser($user);

            (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $user->email
            );
        })->throws(\InvalidArgumentException::class, 'already a member');

        it('throws exception if active invitation already exists', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $email = 'john@example.com';

            // Create first invitation
            (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $email
            );

            // Try to create another invitation with same email
            (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $email
            );
        })->throws(\InvalidArgumentException::class, 'active invitation already exists');

        it('allows sending invitation after previous one expired', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $email = 'john@example.com';

            // Create first invitation with 1 day expiration
            $invitation1 = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $email,
                OrganizationRole::MEMBER,
                1
            );

            // Manually update expires_at to past date
            $invitation1->update(['expires_at' => now()->subDay()]);

            // Send new invitation
            $invitation2 = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $email
            );

            expect($invitation2->id)->not->toBe($invitation1->id);
        });

        it('allows resending invitation after previous one declined', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $email = 'john@example.com';

            // Create first invitation
            $invitation1 = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $email
            );

            // Decline it
            $invitation1->decline();

            // Send new invitation
            $invitation2 = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $email
            );

            expect($invitation2->id)->not->toBe($invitation1->id);
        });
    });

    describe('AcceptInvitation Action', function () {
        it('can accept an invitation', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $user = UserFactory::new()->create(['email' => 'john@example.com']);

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $user->email,
                OrganizationRole::MEMBER
            );

            Event::fake();

            $result = (new AcceptInvitation)->handle($invitation, $user);

            expect($result)->toBeInstanceOf(Organization::class)
                ->and($result->is($organization))->toBeTrue()
                ->and($invitation->fresh()->isAccepted())->toBeTrue()
                ->and($organization->users()->where('users.id', $user->id)->exists())->toBeTrue()
                ->and($organization->members()->where('users.id', $user->id)->exists())->toBeTrue();

            Event::assertDispatched(InvitationAccepted::class, function (InvitationAccepted $event) use ($invitation) {
                return $event->invitation->is($invitation);
            });
        });

        it('can accept an invitation with admin role', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $user = UserFactory::new()->create(['email' => 'admin@example.com']);

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $user->email,
                OrganizationRole::ADMINISTRATOR
            );

            Event::fake();

            (new AcceptInvitation)->handle($invitation, $user);

            expect($organization->administrators()->where('users.id', $user->id)->exists())->toBeTrue();
        });

        it('throws exception if invitation is already accepted', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $user = UserFactory::new()->create(['email' => 'john@example.com']);

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $user->email
            );

            $invitation->accept($user);

            (new AcceptInvitation)->handle($invitation, $user);
        })->throws(\InvalidArgumentException::class, 'already been accepted');

        it('throws exception if invitation is declined', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $user = UserFactory::new()->create(['email' => 'john@example.com']);

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $user->email
            );

            $invitation->decline();

            (new AcceptInvitation)->handle($invitation, $user);
        })->throws(\InvalidArgumentException::class, 'already been declined');

        it('throws exception if invitation has expired', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $user = UserFactory::new()->create(['email' => 'john@example.com']);

            $invitation = Invitation::factory()
                ->for($organization)
                ->create([
                    'email' => $user->email,
                    'expires_at' => now()->subDay(),
                ]);

            (new AcceptInvitation)->handle($invitation, $user);
        })->throws(\InvalidArgumentException::class, 'invitation has expired');

        it('throws exception if email does not match', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $invitation = Invitation::factory()
                ->for($organization)
                ->forInvitedBy($invitedBy)
                ->create(['email' => 'john@example.com']);

            $user = UserFactory::new()->create(['email' => 'jane@example.com']);

            (new AcceptInvitation)->handle($invitation, $user);
        })->throws(\InvalidArgumentException::class, 'does not match the invitation');

        it('throws exception if user is already a member', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $user = UserFactory::new()->create(['email' => 'john@example.com']);

            $organization->addUser($user);

            $invitation = Invitation::factory()
                ->for($organization)
                ->forInvitedBy($invitedBy)
                ->create(['email' => $user->email]);

            (new AcceptInvitation)->handle($invitation, $user);
        })->throws(\InvalidArgumentException::class, 'already a member');
    });

    describe('DeclineInvitation Action', function () {
        it('can decline an invitation', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'john@example.com'
            );

            Event::fake();

            $result = (new DeclineInvitation)->handle($invitation);

            expect($result)->toBeInstanceOf(Invitation::class)
                ->and($result->isDeclined())->toBeTrue();

            Event::assertDispatched(InvitationDeclined::class, function (InvitationDeclined $event) use ($invitation) {
                return $event->invitation->is($invitation);
            });
        });

        it('throws exception if invitation is already accepted', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $user = UserFactory::new()->create(['email' => 'john@example.com']);

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                $user->email
            );

            $invitation->accept($user);

            (new DeclineInvitation)->handle($invitation);
        })->throws(\InvalidArgumentException::class, 'already been accepted');

        it('throws exception if invitation is already declined', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'john@example.com'
            );

            $invitation->decline();

            (new DeclineInvitation)->handle($invitation);
        })->throws(\InvalidArgumentException::class, 'already been declined');

        it('throws exception if invitation has expired', function () {
            $invitation = Invitation::factory()
                ->create(['expires_at' => now()->subDay()]);

            (new DeclineInvitation)->handle($invitation);
        })->throws(\InvalidArgumentException::class, 'invitation has expired');
    });

    describe('ResendInvitation Action', function () {
        it('can resend an invitation', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();

            $invitation = (new SendInvitation)->handle(
                $organization,
                $invitedBy,
                'john@example.com'
            );

            $oldToken = $invitation->token;
            $oldExpiration = $invitation->expires_at;

            // Sleep to ensure time difference
            sleep(1);

            $result = (new ResendInvitation)->handle($invitation);

            expect($result)->toBeInstanceOf(Invitation::class)
                ->and($result->token)->not->toBe($oldToken)
                ->and($result->expires_at)->not->toBe($oldExpiration)
                ->and($result->expires_at->isAfter($oldExpiration))->toBeTrue();
        });

        it('throws exception if invitation is accepted', function () {
            $user = UserFactory::new()->create(['email' => 'john@example.com']);

            $invitation = Invitation::factory()
                ->accepted()
                ->create(['email' => $user->email, 'user_id' => $user->id]);

            (new ResendInvitation)->handle($invitation);
        })->throws(\InvalidArgumentException::class, 'Cannot resend');

        it('throws exception if invitation is declined', function () {
            $invitation = Invitation::factory()
                ->declined()
                ->create();

            (new ResendInvitation)->handle($invitation);
        })->throws(\InvalidArgumentException::class, 'Cannot resend');
    });

    describe('Invitation Model', function () {
        it('has correct relationships', function () {
            $organization = Organization::factory()->create();
            $invitedBy = UserFactory::new()->create();
            $acceptedBy = UserFactory::new()->create(['email' => 'john@example.com']);

            $invitation = Invitation::factory()
                ->for($organization)
                ->forInvitedBy($invitedBy)
                ->create(['email' => $acceptedBy->email, 'user_id' => $acceptedBy->id]);

            expect($invitation->organization)->toBeInstanceOf(Organization::class)
                ->and($invitation->invitedByUser)->not->toBeNull()
                ->and($invitation->invitedByUser->id)->toBe($invitedBy->id)
                ->and($invitation->invitedUser)->not->toBeNull()
                ->and($invitation->invitedUser->id)->toBe($acceptedBy->id);
        });

        it('can check if invitation is pending', function () {
            $pending = Invitation::factory()->pending()->create();
            $accepted = Invitation::factory()->accepted()->create();
            $declined = Invitation::factory()->declined()->create();

            expect($pending->isPending())->toBeTrue()
                ->and($accepted->isPending())->toBeFalse()
                ->and($declined->isPending())->toBeFalse();
        });

        it('can check if invitation is accepted', function () {
            $pending = Invitation::factory()->pending()->create();
            $accepted = Invitation::factory()->accepted()->create();
            $declined = Invitation::factory()->declined()->create();

            expect($pending->isAccepted())->toBeFalse()
                ->and($accepted->isAccepted())->toBeTrue()
                ->and($declined->isAccepted())->toBeFalse();
        });

        it('can check if invitation is declined', function () {
            $pending = Invitation::factory()->pending()->create();
            $accepted = Invitation::factory()->accepted()->create();
            $declined = Invitation::factory()->declined()->create();

            expect($pending->isDeclined())->toBeFalse()
                ->and($accepted->isDeclined())->toBeFalse()
                ->and($declined->isDeclined())->toBeTrue();
        });

        it('can check if invitation is expired', function () {
            $valid = Invitation::factory()->create(['expires_at' => now()->addDay()]);
            $expired = Invitation::factory()->expired()->create();

            expect($valid->isExpired())->toBeFalse()
                ->and($expired->isExpired())->toBeTrue();
        });

        it('can check if invitation is valid', function () {
            $valid = Invitation::factory()->pending()->create(['expires_at' => now()->addDay()]);
            $expiredPending = Invitation::factory()->pending()->expired()->create();
            $accepted = Invitation::factory()->accepted()->create();

            expect($valid->isValid())->toBeTrue()
                ->and($expiredPending->isValid())->toBeFalse()
                ->and($accepted->isValid())->toBeFalse();
        });

        it('can get role enum', function () {
            $memberInvitation = Invitation::factory()->member()->create();
            $adminInvitation = Invitation::factory()->admin()->create();

            expect($memberInvitation->getRoleEnum())->toBe(OrganizationRole::MEMBER)
                ->and($adminInvitation->getRoleEnum())->toBe(OrganizationRole::ADMINISTRATOR);
        });

        it('tracks created_at timestamp', function () {
            $invitation = Invitation::factory()->create();

            expect($invitation->created_at)->toBeTruthy()
                ->and($invitation->created_at->isToday())->toBeTrue();
        });

        it('supports soft deletes', function () {
            $invitation = Invitation::factory()->create();

            $invitation->delete();

            expect($invitation->trashed())->toBeTrue()
                ->and(Invitation::count())->toBe(0)
                ->and(Invitation::withTrashed()->count())->toBe(1);
        });
    });

    describe('Invitation Events', function () {
        it('InvitationSent event is dispatchable', function () {
            $invitation = Invitation::factory()->create();

            expect(method_exists(InvitationSent::class, 'dispatch'))->toBeTrue();
        });

        it('InvitationAccepted event is dispatchable', function () {
            $invitation = Invitation::factory()->create();

            expect(method_exists(InvitationAccepted::class, 'dispatch'))->toBeTrue();
        });

        it('InvitationDeclined event is dispatchable', function () {
            $invitation = Invitation::factory()->create();

            expect(method_exists(InvitationDeclined::class, 'dispatch'))->toBeTrue();
        });

        it('InvitationSent event can be serialized for queues', function () {
            $invitation = Invitation::factory()->create();
            $event = new InvitationSent($invitation);

            expect(method_exists($event, '__serialize'))->toBeTrue();
        });

        it('InvitationAccepted event can be serialized for queues', function () {
            $invitation = Invitation::factory()->create();
            $event = new InvitationAccepted($invitation);

            expect(method_exists($event, '__serialize'))->toBeTrue();
        });

        it('InvitationDeclined event can be serialized for queues', function () {
            $invitation = Invitation::factory()->create();
            $event = new InvitationDeclined($invitation);

            expect(method_exists($event, '__serialize'))->toBeTrue();
        });
    });
});
