<?php

use CleaniqueCoders\LaravelOrganization\Actions\CreateNewOrganization;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Workbench\App\Models\User;

// Public routes
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return view('welcome');
});

// Authentication Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    throw ValidationException::withMessages([
        'email' => ['The provided credentials do not match our records.'],
    ]);
})->name('login.store');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    Auth::login($user);

    CreateNewOrganization::run($user);

    return redirect('/dashboard');
})->name('register.store');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/organizations', function () {
        /** @var \Workbench\App\Models\User $user */
        $user = Auth::user();
        $organizations = Organization::with('owner')->get();

        return response()->json([
            'current_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'organization_id' => $user->getOrganizationId(),
            ],
            'total' => $organizations->count(),
            'organizations' => $organizations,
        ]);
    });

    Route::get('/users', function () {
        $users = User::with(['currentOrganization', 'organizations', 'ownedOrganizations'])->get();

        return response()->json([
            'total' => $users->count(),
            'users' => $users,
        ]);
    });

    Route::post('/create-organization', function (Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        /** @var \Workbench\App\Models\User $user */
        $user = Auth::user();

        // Create organization using the action
        $organization = (new CreateNewOrganization)->handle(
            $user,
            false, // not default since user might already have organizations
            $request->name,
            $request->description
        );

        // Set as current organization
        $user->setOrganizationId($organization->id);
        $user->save();

        return response()->json([
            'message' => 'Organization created successfully!',
            'organization' => $organization,
        ]);
    });

    Route::post('/switch-organization/{organizationId}', function ($organizationId) {
        /** @var \Workbench\App\Models\User $user */
        $user = Auth::user();

        // Verify user has access to this organization
        if (! $user->belongsToOrganization($organizationId) && ! $user->ownsOrganization($organizationId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user->setOrganizationId($organizationId);
        $user->save();

        $organization = Organization::find($organizationId);

        return response()->json([
            'message' => "Switched to organization: {$organization->name}",
            'organization' => $organization,
        ]);
    });

    Route::get('/my-organizations', function () {
        /** @var \Workbench\App\Models\User $user */
        $user = Auth::user();

        $ownedOrganizations = $user->ownedOrganizations;
        $memberOrganizations = $user->organizations;

        return response()->json([
            'current_organization_id' => $user->getOrganizationId(),
            'owned_organizations' => $ownedOrganizations,
            'member_organizations' => $memberOrganizations,
        ]);
    });
});

// Test route (for backwards compatibility)
Route::get('/test-organization', function () {
    // Create a test user
    $user = User::factory()->create([
        'name' => 'Test User',
    ]);

    // Create an organization for the user
    $organization = (new CreateNewOrganization)->handle($user);

    // Update user's current organization
    $user->setOrganizationId($organization->id);
    $user->save();

    return response()->json([
        'message' => 'Organization created successfully!',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'organization_id' => $user->getOrganizationId(),
        ],
        'organization' => [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'uuid' => $organization->uuid,
            'owner_id' => $organization->owner_id,
        ],
        'relationships' => [
            'user_owns_organization' => $user->ownsOrganization($organization->id),
            'user_belongs_to_organization' => $user->belongsToOrganization($organization->id),
            'organization_is_owned_by_user' => $organization->isOwnedBy($user),
        ],
    ]);
});

Route::get('/test-livewire', function () {
    return view('test-livewire');
});
