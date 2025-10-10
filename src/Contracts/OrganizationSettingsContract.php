<?php

namespace CleaniqueCoders\LaravelOrganization\Contracts;

/**
 * Organization Settings Contract
 *
 * Defines methods for managing organization settings and configuration.
 * This contract follows the Single Responsibility Principle by focusing
 * solely on settings management operations.
 */
interface OrganizationSettingsContract
{
    /**
     * Apply default settings from configuration.
     */
    public function applyDefaultSettings(): void;

    /**
     * Get default settings from configuration.
     */
    public static function getDefaultSettings(): array;

    /**
     * Validate settings according to validation rules.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateSettings(): void;

    /**
     * Reset settings to defaults.
     */
    public function resetSettingsToDefaults(): void;

    /**
     * Merge additional settings with current settings.
     */
    public function mergeSettings(array $newSettings): void;

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null);

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, $value): void;

    /**
     * Check if a setting exists.
     */
    public function hasSetting(string $key): bool;

    /**
     * Remove a setting.
     */
    public function removeSetting(string $key): void;

    /**
     * Get all settings as array.
     */
    public function getAllSettings(): array;
}
