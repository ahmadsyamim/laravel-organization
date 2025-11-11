<?php

namespace CleaniqueCoders\LaravelOrganization\Tests;

use CleaniqueCoders\LaravelOrganization\LaravelOrganizationServiceProvider;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CleaniqueCoders\\LaravelOrganization\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            LaravelOrganizationServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     * This runs before service providers are registered.
     */
    protected function defineEnvironment($app)
    {
        // Set encryption key for Livewire tests
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Configure the User and Organization models BEFORE service provider registration
        $app['config']->set('organization.user-model', User::class);
        $app['config']->set('organization.organization-model', Organization::class);
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Create users table for testing
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignIdFor(Organization::class)->nullable()->constrained()->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        // Create organizations table
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(User::class, 'owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Create organization_users pivot table
        Schema::create('organization_users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->enum('role', ['member', 'administrator'])->default('member');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'role']);
            $table->index(['user_id', 'role']);
        });

        // Create invitations table
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(Organization::class)->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->index();
            $table->string('token')->unique();
            $table->string('role');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expires_at');
            $table->softDeletes();
            $table->timestamps();

            // Composite index for querying pending invitations by organization and email
            $table->index(['organization_id', 'email', 'accepted_at', 'declined_at']);

            // Index for finding invitations by token
            $table->index('token');
        });
    }
}
