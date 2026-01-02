<?php

/**
 * Configuration file for CleaniqueCoders/LaravelOrganization
 *
 * This configuration file contains settings for the Laravel Organization package
 * that manages organization-based tenancy in your Laravel application.
 */

use CleaniqueCoders\LaravelOrganization\Models\Invitation;
use CleaniqueCoders\LaravelOrganization\Models\Organization;
use CleaniqueCoders\LaravelOrganization\Models\OwnershipTransferRequest;
use Workbench\App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This option defines the User model class that will be used throughout
    | the organization system. The model should extend Illuminate\Foundation\Auth\User
    | or implement the necessary contracts for authentication.
    |
    | The User model will be used for:
    | - Organization ownership relationships
    | - Organization membership relationships
    | - Authentication and authorization within organizations
    |
    | For better SOLID compliance, consider implementing UserOrganizationContract.
    |
    | Default: Illuminate\Foundation\Auth\User::class
    |
    */

    'user-model' => User::class,

    /*
    |--------------------------------------------------------------------------
    | Organization Model
    |--------------------------------------------------------------------------
    |
    | This option defines the Organization model class that will be used throughout
    | the organization system. The model should extend CleaniqueCoders\LaravelOrganization\Models\Organization
    | or implement the necessary contracts for organization management.
    |
    | The Organization model will be used for:
    | - Organization data management and persistence
    | - Organization relationships with users and other entities
    | - Organization settings and configuration storage
    | - Multi-tenancy and organization scoping
    |
    | For better SOLID compliance, the model should implement:
    | - OrganizationContract (core functionality)
    | - OrganizationMembershipContract (user management)
    | - OrganizationOwnershipContract (ownership management)
    | - OrganizationSettingsContract (settings management)
    |
    | Default: CleaniqueCoders\LaravelOrganization\Models\Organization::class
    |
    */

    'organization-model' => Organization::class,

    /*
    |--------------------------------------------------------------------------
    | Invitation Model
    |--------------------------------------------------------------------------
    |
    | This option defines the Invitation model class that will be used throughout
    | the organization system.
    |
    */

    'invitation-model' => Invitation::class,

    /*
    |--------------------------------------------------------------------------
    | Ownership Transfer Request Model
    |--------------------------------------------------------------------------
    |
    | This option defines the OwnershipTransferRequest model class that will be
    | used for handling ownership transfer requests.
    |
    */

    'ownership-transfer-request-model' => OwnershipTransferRequest::class,

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Customize the database table names used by the organization system.
    |
    */

    'tables' => [
        'organizations' => 'organizations',
        'organization_users' => 'organization_users',
        'invitations' => 'organization_invitations',
        'ownership_transfer_requests' => 'organization_ownership_transfer_requests',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Organization Settings
    |--------------------------------------------------------------------------
    |
    | These are the default settings that will be applied to new organizations
    | when they are created. Organizations can override these settings individually
    | using the setSetting() method on the Organization model.
    |
    | These settings are stored in the organization's 'settings' JSON column.
    |
    */

    'default-settings' => [

        /*
        |----------------------------------------------------------------------
        | Contact Information
        |----------------------------------------------------------------------
        */
        'contact' => [
            'email' => null,
            'phone' => null,
            'fax' => null,
            'website' => null,
        ],

        /*
        |----------------------------------------------------------------------
        | Address Information
        |----------------------------------------------------------------------
        */
        'address' => [
            'street' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
        ],

        /*
        |----------------------------------------------------------------------
        | Social Media Links
        |----------------------------------------------------------------------
        */
        'social_media' => [
            'facebook' => null,
            'twitter' => null,
            'linkedin' => null,
            'instagram' => null,
            'youtube' => null,
            'github' => null,
        ],

        /*
        |----------------------------------------------------------------------
        | Business Information
        |----------------------------------------------------------------------
        */
        'business' => [
            'industry' => null,
            'company_size' => null,
            'founded_year' => null,
            'tax_id' => null,
            'registration_number' => null,
        ],

        /*
        |----------------------------------------------------------------------
        | Application Settings
        |----------------------------------------------------------------------
        */
        'app' => [
            'timezone' => 'UTC',
            'locale' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ],

        /*
        |----------------------------------------------------------------------
        | Feature Toggles
        |----------------------------------------------------------------------
        */
        'features' => [
            'notifications' => true,
            'analytics' => true,
            'api_access' => false,
            'custom_branding' => false,
            'multi_language' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | UI/UX Preferences
        |----------------------------------------------------------------------
        */
        'ui' => [
            'theme' => 'light', // light, dark, auto
            'sidebar_collapsed' => false,
            'layout' => 'default',
            'items_per_page' => 25,
        ],

        /*
        |----------------------------------------------------------------------
        | Security Settings
        |----------------------------------------------------------------------
        */
        'security' => [
            'two_factor_required' => false,
            'password_expires_days' => 90,
            'session_timeout_minutes' => 120,
            'allowed_domains' => [],
        ],

        /*
        |----------------------------------------------------------------------
        | Billing & Subscription
        |----------------------------------------------------------------------
        */
        'billing' => [
            'plan' => 'free',
            'billing_cycle' => 'monthly', // monthly, yearly
            'auto_renew' => true,
            'billing_email' => null,
        ],

        /*
        |----------------------------------------------------------------------
        | Integration Settings
        |----------------------------------------------------------------------
        */
        'integrations' => [
            'email_provider' => 'default',
            'storage_provider' => 'local',
            'payment_gateway' => null,
            'sms_provider' => null,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Define validation rules for organization settings. These rules will be
    | applied when updating organization settings to ensure data integrity.
    |
    */

    'validation_rules' => [
        'contact.email' => 'nullable|email',
        'contact.phone' => 'nullable|string|max:20',
        'contact.website' => 'nullable|url',
        'address.postal_code' => 'nullable|string|max:20',
        'address.country' => 'nullable|string|size:2', // ISO 2-letter country code
        'app.timezone' => 'nullable|string', // Simplified timezone validation
        'app.locale' => 'nullable|string|size:2',
        'app.currency' => 'nullable|string|size:3', // ISO 3-letter currency code
        'features.*' => 'boolean',
        'ui.theme' => 'nullable|string|in:light,dark,auto',
        'ui.items_per_page' => 'nullable|integer|min:5|max:100',
        'security.password_expires_days' => 'nullable|integer|min:1|max:365',
        'security.session_timeout_minutes' => 'nullable|integer|min:5|max:1440',
        'billing.plan' => 'nullable|string',
        'billing.billing_cycle' => 'nullable|string|in:monthly,yearly',
    ],

];
