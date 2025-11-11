<?php

use CleaniqueCoders\LaravelOrganization\Database\Factories\InvitationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\OrganizationFactory;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Mail\InvitationMail;

beforeEach(function () {
    $this->user = UserFactory::new()->create();
    $this->organization = OrganizationFactory::new()->ownedBy($this->user)->create();
    $this->invitation = InvitationFactory::new()
        ->for($this->organization)
        ->create(['email' => 'invitee@example.com']);
});

describe('InvitationMail', function () {
    it('has correct subject', function () {
        $mail = new InvitationMail($this->invitation);

        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe("You're invited to join {$this->organization->name}");
    });

    it('can be constructed with invitation', function () {
        $mail = new InvitationMail($this->invitation);

        expect($mail->invitation)->toBeInstanceOf(\CleaniqueCoders\LaravelOrganization\Models\Invitation::class)
            ->and($mail->invitation->id)->toBe($this->invitation->id);
    });

    it('can be constructed with accept and decline URLs', function () {
        $acceptUrl = 'https://example.com/accept/token123';
        $declineUrl = 'https://example.com/decline/token123';

        $mail = new InvitationMail($this->invitation, $acceptUrl, $declineUrl);

        expect($mail->acceptUrl)->toBe($acceptUrl)
            ->and($mail->declineUrl)->toBe($declineUrl);
    });

    it('handles null accept and decline URLs', function () {
        $mail = new InvitationMail($this->invitation, null, null);

        expect($mail->acceptUrl)->toBeNull()
            ->and($mail->declineUrl)->toBeNull();
    });

    it('uses correct view', function () {
        $mail = new InvitationMail($this->invitation);

        $content = $mail->content();

        expect($content->view)->toBe('org::emails.invitation');
    });

    it('passes invitation to view', function () {
        $mail = new InvitationMail($this->invitation);

        $content = $mail->content();

        expect($content->with)->toHaveKey('invitation')
            ->and($content->with['invitation']->id)->toBe($this->invitation->id);
    });

    it('passes organization to view', function () {
        $mail = new InvitationMail($this->invitation);

        $content = $mail->content();

        expect($content->with)->toHaveKey('organization')
            ->and($content->with['organization']->id)->toBe($this->organization->id);
    });

    it('passes accept and decline URLs to view', function () {
        $acceptUrl = 'https://example.com/accept/token123';
        $declineUrl = 'https://example.com/decline/token123';

        $mail = new InvitationMail($this->invitation, $acceptUrl, $declineUrl);

        $content = $mail->content();

        expect($content->with)->toHaveKey('acceptUrl')
            ->and($content->with['acceptUrl'])->toBe($acceptUrl)
            ->and($content->with)->toHaveKey('declineUrl')
            ->and($content->with['declineUrl'])->toBe($declineUrl);
    });

    it('has no attachments by default', function () {
        $mail = new InvitationMail($this->invitation);

        $attachments = $mail->attachments();

        expect($attachments)->toBeArray()
            ->and($attachments)->toBeEmpty();
    });

    it('is queueable', function () {
        $mail = new InvitationMail($this->invitation);

        $traits = class_uses($mail);

        expect($traits)->toHaveKey('Illuminate\Bus\Queueable');
    });

    it('serializes models', function () {
        $mail = new InvitationMail($this->invitation);

        $traits = class_uses($mail);

        expect($traits)->toHaveKey('Illuminate\Queue\SerializesModels');
    });
});
