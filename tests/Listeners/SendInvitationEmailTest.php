<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\InvitationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use CleaniqueCoders\LaravelOrganization\Listeners\SendInvitationEmail;
use CleaniqueCoders\LaravelOrganization\Mail\InvitationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Mail::fake();

    $this->user = UserFactory::new()->create();
    $this->organization = OrganizationFactory::new()->ownedBy($this->user)->create();
});

describe('SendInvitationEmail Listener', function () {
    it('sends invitation email when handling event', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'invitee@example.com']);

        $event = new InvitationSent($invitation);
        $listener = new SendInvitationEmail;

        $listener->handle($event);

        Mail::assertSent(InvitationMail::class, function ($mail) use ($invitation) {
            return $mail->hasTo('invitee@example.com') &&
                   $mail->invitation->id === $invitation->id;
        });
    });

    it('includes accept and decline URLs when routes are defined', function () {
        // Define the routes
        Route::get('/invitations/accept/{token}', fn () => 'accept')
            ->name('invitations.accept');
        Route::get('/invitations/decline/{token}', fn () => 'decline')
            ->name('invitations.decline');

        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'invitee@example.com']);

        $event = new InvitationSent($invitation);
        $listener = new SendInvitationEmail;

        $listener->handle($event);

        Mail::assertSent(InvitationMail::class, function ($mail) use ($invitation) {
            return $mail->acceptUrl !== null &&
                   $mail->declineUrl !== null &&
                   str_contains($mail->acceptUrl, $invitation->token) &&
                   str_contains($mail->declineUrl, $invitation->token);
        });
    });

    it('handles missing routes gracefully', function () {
        // Don't define routes, so they should be null
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'invitee@example.com']);

        $event = new InvitationSent($invitation);
        $listener = new SendInvitationEmail;

        // Should not throw exception
        $listener->handle($event);

        Mail::assertSent(InvitationMail::class, function ($mail) {
            return $mail->acceptUrl === null &&
                   $mail->declineUrl === null;
        });
    });

    it('sends email to correct recipient', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'test@example.com']);

        $event = new InvitationSent($invitation);
        $listener = new SendInvitationEmail;

        $listener->handle($event);

        Mail::assertSent(InvitationMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    });

    it('passes invitation to mail class', function () {
        $invitation = InvitationFactory::new()
            ->for($this->organization)
            ->create(['email' => 'invitee@example.com']);

        $event = new InvitationSent($invitation);
        $listener = new SendInvitationEmail;

        $listener->handle($event);

        Mail::assertSent(InvitationMail::class, function ($mail) use ($invitation) {
            return $mail->invitation->id === $invitation->id &&
                   $mail->invitation->organization_id === $invitation->organization_id &&
                   $mail->invitation->email === $invitation->email;
        });
    });
});
