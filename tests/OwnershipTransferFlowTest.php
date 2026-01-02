<?php

use CleaniqueCoders\LaravelOrganization\Actions\AcceptOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Actions\CancelOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use CleaniqueCoders\LaravelOrganization\Actions\DeclineOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Actions\InitiateOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Database\Factories\UserFactory;
use CleaniqueCoders\LaravelOrganization\Enums\OrganizationRole;
use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferAccepted;
use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferCancelled;
use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferDeclined;
use CleaniqueCoders\LaravelOrganization\Events\OwnershipTransferRequested;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->owner = UserFactory::new()->create(['name' => 'Current Owner']);
    $this->newOwner = UserFactory::new()->create(['name' => 'New Owner']);
    $this->organization = (new CreateNewOrganization)->handle($this->owner);
});

describe('InitiateOwnershipTransfer Action', function () {
    it('can initiate an ownership transfer request', function () {
        Event::fake();

        $request = InitiateOwnershipTransfer::run(
            $this->organization,
            $this->owner,
            $this->newOwner,
            'Please take over this organization'
        );

        expect($request)->toBeInstanceOf(OwnershipTransferRequest::class)
            ->and($request->organization_id)->toBe($this->organization->id)
            ->and($request->current_owner_id)->toBe($this->owner->id)
            ->and($request->new_owner_id)->toBe($this->newOwner->id)
            ->and($request->message)->toBe('Please take over this organization')
            ->and($request->isPending())->toBeTrue()
            ->and($request->isValid())->toBeTrue();

        Event::assertDispatched(OwnershipTransferRequested::class);
    });

    it('throws exception when non-owner tries to initiate transfer', function () {
        $nonOwner = UserFactory::new()->create();

        expect(fn () => InitiateOwnershipTransfer::run($this->organization, $nonOwner, $this->newOwner))
            ->toThrow(InvalidArgumentException::class, 'Only the current owner can initiate an ownership transfer.');
    });

    it('throws exception when trying to transfer to self', function () {
        expect(fn () => InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->owner))
            ->toThrow(InvalidArgumentException::class, 'Cannot transfer ownership to yourself.');
    });

    it('throws exception when pending request already exists', function () {
        // Create first request
        InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        // Try to create another
        $anotherUser = UserFactory::new()->create();

        expect(fn () => InitiateOwnershipTransfer::run($this->organization, $this->owner, $anotherUser))
            ->toThrow(InvalidArgumentException::class, 'There is already a pending transfer request for this organization.');
    });

    it('sets expiration time correctly', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        $hoursUntilExpiry = now()->diffInHours($request->expires_at);

        expect($hoursUntilExpiry)->toBeGreaterThanOrEqual(71)
            ->and($hoursUntilExpiry)->toBeLessThanOrEqual(73);
    });
});

describe('AcceptOwnershipTransfer Action', function () {
    it('can accept a transfer request', function () {
        Event::fake();

        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        $organization = AcceptOwnershipTransfer::run($request, $this->newOwner);

        expect($organization->owner_id)->toBe($this->newOwner->id)
            ->and($organization->isOwnedBy($this->newOwner))->toBeTrue()
            ->and($request->fresh()->isAccepted())->toBeTrue();

        Event::assertDispatched(OwnershipTransferAccepted::class);
    });

    it('updates pivot table roles on acceptance', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        AcceptOwnershipTransfer::run($request, $this->newOwner);

        // Previous owner should be administrator
        expect($this->organization->fresh()->getUserRole($this->owner))->toBe(OrganizationRole::ADMINISTRATOR);

        // New owner should have owner role
        expect($this->organization->fresh()->getUserRole($this->newOwner))->toBe(OrganizationRole::OWNER);
    });

    it('throws exception when wrong user tries to accept', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);
        $wrongUser = UserFactory::new()->create();

        expect(fn () => AcceptOwnershipTransfer::run($request, $wrongUser))
            ->toThrow(InvalidArgumentException::class, 'Only the intended new owner can accept this transfer request.');
    });

    it('throws exception when request is expired', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        // Manually expire the request
        $request->update(['expires_at' => now()->subDay()]);

        expect(fn () => AcceptOwnershipTransfer::run($request, $this->newOwner))
            ->toThrow(InvalidArgumentException::class, 'This transfer request has expired.');
    });

    it('throws exception when request is already accepted', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);
        AcceptOwnershipTransfer::run($request, $this->newOwner);

        expect(fn () => AcceptOwnershipTransfer::run($request->fresh(), $this->newOwner))
            ->toThrow(InvalidArgumentException::class, 'This transfer request has already been accepted.');
    });

    it('can accept by token', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        $organization = (new AcceptOwnershipTransfer)->handleByToken($request->token, $this->newOwner);

        expect($organization->owner_id)->toBe($this->newOwner->id);
    });
});

describe('DeclineOwnershipTransfer Action', function () {
    it('can decline a transfer request', function () {
        Event::fake();

        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        $declinedRequest = DeclineOwnershipTransfer::run($request, $this->newOwner);

        expect($declinedRequest->isDeclined())->toBeTrue()
            ->and($this->organization->fresh()->owner_id)->toBe($this->owner->id);

        Event::assertDispatched(OwnershipTransferDeclined::class);
    });

    it('throws exception when wrong user tries to decline', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);
        $wrongUser = UserFactory::new()->create();

        expect(fn () => DeclineOwnershipTransfer::run($request, $wrongUser))
            ->toThrow(InvalidArgumentException::class, 'Only the intended new owner can decline this transfer request.');
    });

    it('can decline by token', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        $declinedRequest = (new DeclineOwnershipTransfer)->handleByToken($request->token, $this->newOwner);

        expect($declinedRequest->isDeclined())->toBeTrue();
    });
});

describe('CancelOwnershipTransfer Action', function () {
    it('can cancel a transfer request', function () {
        Event::fake();

        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        $cancelledRequest = CancelOwnershipTransfer::run($request, $this->owner);

        expect($cancelledRequest->isCancelled())->toBeTrue();

        Event::assertDispatched(OwnershipTransferCancelled::class);
    });

    it('throws exception when non-owner tries to cancel', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        expect(fn () => CancelOwnershipTransfer::run($request, $this->newOwner))
            ->toThrow(InvalidArgumentException::class, 'Only the current owner can cancel this transfer request.');
    });

    it('throws exception when request is already accepted', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);
        AcceptOwnershipTransfer::run($request, $this->newOwner);

        expect(fn () => CancelOwnershipTransfer::run($request->fresh(), $this->owner))
            ->toThrow(InvalidArgumentException::class, 'This transfer request has already been accepted and cannot be cancelled.');
    });
});

describe('OwnershipTransferRequest Model', function () {
    it('has correct status methods', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        expect($request->isPending())->toBeTrue()
            ->and($request->isAccepted())->toBeFalse()
            ->and($request->isDeclined())->toBeFalse()
            ->and($request->isCancelled())->toBeFalse()
            ->and($request->isExpired())->toBeFalse()
            ->and($request->isValid())->toBeTrue();
    });

    it('has correct relationships', function () {
        $request = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        expect($request->organization->id)->toBe($this->organization->id)
            ->and($request->currentOwner->id)->toBe($this->owner->id)
            ->and($request->newOwner->id)->toBe($this->newOwner->id);
    });

    it('has pending scope', function () {
        // Create a pending request
        $pendingRequest = InitiateOwnershipTransfer::run($this->organization, $this->owner, $this->newOwner);

        // Create another org with accepted request
        $owner2 = UserFactory::new()->create();
        $newOwner2 = UserFactory::new()->create();
        $org2 = (new CreateNewOrganization)->handle($owner2);
        $acceptedRequest = InitiateOwnershipTransfer::run($org2, $owner2, $newOwner2);
        AcceptOwnershipTransfer::run($acceptedRequest, $newOwner2);

        $pendingRequests = OwnershipTransferRequest::pending()->get();

        expect($pendingRequests)->toHaveCount(1)
            ->and($pendingRequests->first()->id)->toBe($pendingRequest->id);
    });
});
