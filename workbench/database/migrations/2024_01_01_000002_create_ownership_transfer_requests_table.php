<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = config('organization.tables.ownership_transfer_requests', 'organization_ownership_transfer_requests');
        $organizationsTable = config('organization.tables.organizations', 'organizations');
        $usersTable = (new (config('organization.user-model')))->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($organizationsTable, $usersTable) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignIdFor(config('organization.organization-model'))->constrained($organizationsTable)->cascadeOnDelete();
            $table->foreignId('current_owner_id')->constrained($usersTable)->cascadeOnDelete();
            $table->foreignId('new_owner_id')->constrained($usersTable)->cascadeOnDelete();
            $table->string('token')->unique();
            $table->text('message')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at');
            $table->softDeletes();
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['organization_id', 'new_owner_id']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = config('organization.tables.ownership_transfer_requests', 'organization_ownership_transfer_requests');

        Schema::dropIfExists($tableName);
    }
};
