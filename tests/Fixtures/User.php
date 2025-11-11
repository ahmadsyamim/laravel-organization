<?php

namespace CleaniqueCoders\LaravelOrganization\Tests\Fixtures;

use CleaniqueCoders\LaravelOrganization\Concerns\InteractsWithUserOrganization;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use InteractsWithUserOrganization;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
