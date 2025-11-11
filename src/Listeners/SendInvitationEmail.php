<?php

namespace CleaniqueCoders\LaravelOrganization\Listeners;

use CleaniqueCoders\LaravelOrganization\Events\InvitationSent;
use CleaniqueCoders\LaravelOrganization\Mail\InvitationMail;
use Illuminate\Support\Facades\Mail;

class SendInvitationEmail
{
    /**
     * Handle the event.
     */
    public function handle(InvitationSent $event): void
    {
        // Generate acceptance URL (customize based on your routing)
        try {
            $acceptUrl = route('invitations.accept', [
                'token' => $event->invitation->token,
            ]);

            $declineUrl = route('invitations.decline', [
                'token' => $event->invitation->token,
            ]);
        } catch (\Exception $e) {
            // If routes are not defined, just send without URLs
            $acceptUrl = null;
            $declineUrl = null;
        }

        // Send the invitation email
        Mail::to($event->invitation->email)->send(
            new InvitationMail(
                $event->invitation,
                $acceptUrl,
                $declineUrl,
            )
        );
    }
}
