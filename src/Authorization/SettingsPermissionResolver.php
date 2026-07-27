<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Authorization;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Islamv\FilamentSettingsPlugin\FilamentSettingsPlugin;

/**
 * Centralizes all permission resolution logic.
 *
 * Prevents permission naming logic from scattering across tab classes.
 */
class SettingsPermissionResolver
{
    /**
     * Permission key for accessing the Settings page at all.
     */
    public function getSettingsPagePermissionKey(): string
    {
        return 'page_settings';
    }

    /**
     * Permission key for a main tab.
     */
    public function getTabPermissionKey(string $tabKey): string
    {
        return 'page_settings_' . str_replace('-', '_', $tabKey);
    }

    /**
     * Permission key for a sub-tab.
     */
    public function getSubTabPermissionKey(string $parentTabKey, string $subTabKey): string
    {
        return 'page_settings_' . str_replace('-', '_', $parentTabKey) . '_' . str_replace('-', '_', $subTabKey);
    }

    /**
     * Check if the current user can access the Settings page.
     */
    public function canAccessSettingsPage(): bool
    {
        try {
            $plugin = FilamentSettingsPlugin::get();
        } catch (\Throwable) {
            return true; // During testing or early resolution
        }

        if ($plugin->isShieldEnabled()) {
            return $this->checkShieldPermission($this->getSettingsPagePermissionKey());
        }

        return true;
    }

    /**
     * Check if the current user can access a specific tab.
     */
    public function canAccessTab(string $tabKey): bool
    {
        try {
            $plugin = FilamentSettingsPlugin::get();
        } catch (\Throwable) {
            return true;
        }

        if ($plugin->isShieldEnabled()) {
            return $this->checkShieldPermission($this->getTabPermissionKey($tabKey));
        }

        return true;
    }

    /**
     * Check if the current user can access a specific sub-tab.
     */
    public function canAccessSubTab(string $parentTabKey, string $subTabKey): bool
    {
        try {
            $plugin = FilamentSettingsPlugin::get();
        } catch (\Throwable) {
            return true;
        }

        if ($plugin->isShieldEnabled()) {
            return $this->checkShieldPermission($this->getSubTabPermissionKey($parentTabKey, $subTabKey));
        }

        return true;
    }

    /**
     * Get all permission keys managed by this plugin.
     * Used for Shield custom_permissions registration.
     *
     * @param  array<string>  $tabKeys
     * @param  array<string, array<string>>  $subTabKeys  parent => [subtabs]
     * @return array<string, string>  permission_key => label
     */
    public function getAllPermissionKeys(array $tabKeys, array $subTabKeys = []): array
    {
        $permissions = [
            $this->getSettingsPagePermissionKey() => 'Access Settings Page',
        ];

        foreach ($tabKeys as $tabKey) {
            $permissions[$this->getTabPermissionKey($tabKey)] = 'Settings: ' . ucwords(str_replace('-', ' ', $tabKey));
        }

        foreach ($subTabKeys as $parentKey => $subKeys) {
            foreach ($subKeys as $subKey) {
                $permissions[$this->getSubTabPermissionKey($parentKey, $subKey)] =
                    'Settings: ' . ucwords(str_replace('-', ' ', $parentKey)) . ' / ' . ucwords(str_replace('-', ' ', $subKey));
            }
        }

        return $permissions;
    }

    // ─────────────────────────────────────────
    // Shield-specific
    // ─────────────────────────────────────────

    protected function checkShieldPermission(string $permission): bool
    {
        $user = filament()->auth()?->user();

        if ($user === null) {
            return false;
        }

        if (! method_exists($user, 'can')) {
            return true;
        }

        // Super admins bypass all permission checks in Shield
        $superAdminRole = config('filament-shield.super_admin.name', 'super_admin');

        if (method_exists($user, 'hasRole') && $user->hasRole($superAdminRole)) {
            return true;
        }

        return $user->can($permission);
    }
}
