<?php

use CleaniqueCoders\LaravelOrganization\Actions\AcceptOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Actions\DeclineOwnershipTransfer;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Organization Package Routes
|--------------------------------------------------------------------------
|
| These routes handle organization-related functionality including
| ownership transfer acceptance and decline via email links.
|
*/

Route::middleware(['web', 'auth'])->prefix('organization')->name('organization.')->group(function () {
    // Transfer Ownership Page - Shows the transfer ownership form
    Route::get('/{organization}/transfer', function (Organization $organization) {
        // Verify user is the owner
        if (! $organization->isOwnedBy(Auth::user())) {
            abort(403, 'Only the owner can transfer ownership.');
        }

        return view('org::transfer-ownership', [
            'organization' => $organization,
        ]);
    })->name('transfer.show');

    // Ownership Transfer Routes - Email link handlers
    Route::get('/transfer/{token}/accept', function ($token) {
        $transferRequest = OwnershipTransferRequest::where('token', $token)->firstOrFail();

        try {
            $organization = AcceptOwnershipTransfer::run($transferRequest, Auth::user());

            return redirect()->back()->with('success', "You are now the owner of {$organization->name}!");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    })->name('transfer.accept');

    Route::get('/transfer/{token}/decline', function ($token) {
        $transferRequest = OwnershipTransferRequest::where('token', $token)->firstOrFail();

        try {
            DeclineOwnershipTransfer::run($transferRequest, Auth::user());

            return redirect()->back()->with('info', 'Ownership transfer request declined.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    })->name('transfer.decline');
});
