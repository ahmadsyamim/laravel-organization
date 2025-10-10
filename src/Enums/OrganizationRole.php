<?php

namespace CleaniqueCoders\LaravelOrganization\Enums;

use CleaniqueCoders\Traitify\Concerns\InteractsWithEnum;
use CleaniqueCoders\Traitify\Contracts\Enum as Contract;

enum OrganizationRole: string implements Contract
{
    use InteractsWithEnum;

    case MEMBER = 'member';
    case ADMINISTRATOR = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::MEMBER => 'Member',
            self::ADMINISTRATOR => 'Administrator',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MEMBER => __('Regular member with basic access to organization resources.'),
            self::ADMINISTRATOR => __('Administrator with full management access to the organization.'),
        };
    }

    /**
     * Check if role has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMINISTRATOR;
    }

    /**
     * Check if role is a member.
     */
    public function isMember(): bool
    {
        return $this === self::MEMBER;
    }
}
