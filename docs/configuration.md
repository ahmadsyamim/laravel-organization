# Configuration Guide

## Overview

The Laravel Organization package provides extensive configuration options to customize its behavior for your application needs.

## Configuration File

The main configuration file is located at `config/organization.php` after publishing. Here's a detailed breakdown of all available options:

## Model Configuration

### User Model

```php
'user-model' => App\Models\User::class,
```

Specify the User model class that will be used throughout the organization system. The model should:

- Extend `Illuminate\Foundation\Auth\User`
- Implement authentication contracts
- Optionally implement `UserOrganizationContract` for better integration

### Organization Model

```php
'organization-model' => CleaniqueCoders\LaravelOrganization\Models\Organization::class,
```

Specify the Organization model class. For custom implementations, the model should:

- Extend `CleaniqueCoders\LaravelOrganization\Models\Organization` OR
- Implement the required contracts:
  - `OrganizationContract`
  - `OrganizationMembershipContract`
  - `OrganizationOwnershipContract`
  - `OrganizationSettingsContract`

## Default Organization Settings

The `default-settings` configuration defines initial settings for new organizations:

### Contact Information

```php
'contact' => [
    'email' => null,
    'phone' => null,
    'fax' => null,
    'website' => null,
],
```

### Address Information

```php
'address' => [
    'street' => null,
    'city' => null,
    'state' => null,
    'postal_code' => null,
    'country' => null, // ISO 2-letter country code
],
```

### Social Media Links

```php
'social_media' => [
    'facebook' => null,
    'twitter' => null,
    'linkedin' => null,
    'instagram' => null,
    'youtube' => null,
    'github' => null,
],
```

### Business Information

```php
'business' => [
    'industry' => null,
    'company_size' => null,
    'founded_year' => null,
    'tax_id' => null,
    'registration_number' => null,
],
```

### Application Settings

```php
'app' => [
    'timezone' => 'UTC',
    'locale' => 'en',
    'currency' => 'USD', // ISO 3-letter currency code
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
],
```

### Feature Toggles

```php
'features' => [
    'notifications' => true,
    'analytics' => true,
    'api_access' => false,
    'custom_branding' => false,
    'multi_language' => false,
],
```

### UI/UX Preferences

```php
'ui' => [
    'theme' => 'light', // light, dark, auto
    'sidebar_collapsed' => false,
    'layout' => 'default',
    'items_per_page' => 25,
],
```

### Security Settings

```php
'security' => [
    'two_factor_required' => false,
    'password_expires_days' => 90,
    'session_timeout_minutes' => 120,
    'allowed_domains' => [],
],
```

### Billing & Subscription

```php
'billing' => [
    'plan' => 'free',
    'billing_cycle' => 'monthly', // monthly, yearly
    'auto_renew' => true,
    'billing_email' => null,
],
```

### Integration Settings

```php
'integrations' => [
    'email_provider' => 'default',
    'storage_provider' => 'local',
    'payment_gateway' => null,
    'sms_provider' => null,
],
```

## Validation Rules

The package includes comprehensive validation rules for organization settings:

```php
'validation_rules' => [
    'contact.email' => 'nullable|email',
    'contact.phone' => 'nullable|string|max:20',
    'contact.website' => 'nullable|url',
    'address.postal_code' => 'nullable|string|max:20',
    'address.country' => 'nullable|string|size:2',
    'app.timezone' => 'nullable|string',
    'app.locale' => 'nullable|string|size:2',
    'app.currency' => 'nullable|string|size:3',
    'features.*' => 'boolean',
    'ui.theme' => 'nullable|string|in:light,dark,auto',
    'ui.items_per_page' => 'nullable|integer|min:5|max:100',
    'security.password_expires_days' => 'nullable|integer|min:1|max:365',
    'security.session_timeout_minutes' => 'nullable|integer|min:5|max:1440',
    'billing.plan' => 'nullable|string',
    'billing.billing_cycle' => 'nullable|string|in:monthly,yearly',
],
```

## Customizing Configuration

### Adding Custom Settings

You can extend the default settings by adding your own sections:

```php
'default-settings' => [
    // ... existing settings

    'custom' => [
        'api_key' => null,
        'webhook_url' => null,
        'custom_field' => 'default_value',
    ],
],
```

### Adding Custom Validation Rules

Extend validation rules for your custom settings:

```php
'validation_rules' => [
    // ... existing rules

    'custom.api_key' => 'nullable|string|min:32',
    'custom.webhook_url' => 'nullable|url',
    'custom.custom_field' => 'required|string|max:255',
],
```

### Using Custom Models

Create a custom organization model:

```php
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationMembershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationOwnershipContract;
use CleaniqueCoders\LaravelOrganization\Contracts\OrganizationSettingsContract;
use CleaniqueCoders\LaravelOrganization\Models\Organization;

class CustomOrganization extends Organization implements
    OrganizationContract,
    OrganizationMembershipContract,
    OrganizationOwnershipContract,
    OrganizationSettingsContract
{
    // Add custom methods and properties

    public function getCustomAttribute(): string
    {
        return $this->getSetting('custom.custom_field', 'default');
    }
}
```

Update your configuration:

```php
'organization-model' => App\Models\CustomOrganization::class,
```

## Environment-Specific Configuration

You can override configuration values using environment variables by modifying the config file:

```php
'default-settings' => [
    'app' => [
        'timezone' => env('DEFAULT_ORG_TIMEZONE', 'UTC'),
        'locale' => env('DEFAULT_ORG_LOCALE', 'en'),
        'currency' => env('DEFAULT_ORG_CURRENCY', 'USD'),
    ],
    'features' => [
        'api_access' => env('DEFAULT_ORG_API_ACCESS', false),
        'analytics' => env('DEFAULT_ORG_ANALYTICS', true),
    ],
],
```

Then in your `.env` file:

```env
DEFAULT_ORG_TIMEZONE=America/New_York
DEFAULT_ORG_LOCALE=en
DEFAULT_ORG_CURRENCY=USD
DEFAULT_ORG_API_ACCESS=true
DEFAULT_ORG_ANALYTICS=false
```

## Testing Configuration

For testing, you can override configuration in your tests:

```php
use Illuminate\Support\Facades\Config;

public function test_custom_organization_settings()
{
    Config::set('organization.default-settings.app.timezone', 'America/Chicago');

    $organization = Organization::factory()->create();

    $this->assertEquals('America/Chicago', $organization->getSetting('app.timezone'));
}
```
