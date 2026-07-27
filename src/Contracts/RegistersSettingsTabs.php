<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Contracts;

/**
 * Implement this interface on a ServiceProvider to register tabs
 * from an external package into the SettingsPlugin.
 *
 * Example usage in a third-party ServiceProvider:
 *
 *   public function boot(): void
 *   {
 *       $registry = app(\Islamv\FilamentSettingsPlugin\Registry\SettingsRegistry::class);
 *       $registry->register(PaymentSettingsTab::make());
 *   }
 */
interface RegistersSettingsTabs
{
    /**
     * Register tabs and sub-tabs with the global SettingsRegistry.
     */
    public function registerSettingsTabs(): void;
}
