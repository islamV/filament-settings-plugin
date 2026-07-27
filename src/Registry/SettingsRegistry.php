<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Registry;

use Closure;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;

/**
 * Central registry that accumulates all tabs and sub-tabs from:
 *   1. Plugin defaults
 *   2. Automatic discovery
 *   3. Manual registration via plugin fluent API
 *   4. External package ServiceProviders
 *
 * Precedence (highest to lowest):
 *   Manual registration → Application discovery → Plugin defaults
 */
class SettingsRegistry
{
    /** @var array<string, SettingsTab> */
    protected array $tabs = [];

    /** @var array<string, SettingsSubTab> */
    protected array $subTabs = [];

    // ─────────────────────────────────────────
    // Tab management
    // ─────────────────────────────────────────

    /**
     * Register a main tab. If a tab with the same key already exists,
     * it will NOT be overwritten (first-registered wins for defaults).
     * Use replaceTab() to explicitly override.
     */
    public function registerTab(SettingsTab $tab, bool $force = false): void
    {
        $key = $tab->getKey();

        if (! $force && isset($this->tabs[$key])) {
            return;
        }

        $this->tabs[$key] = $tab;
    }

    public function removeTab(string $key): void
    {
        unset($this->tabs[$key]);
    }

    public function replaceTab(string $key, SettingsTab $tab): void
    {
        $this->tabs[$key] = $tab;
    }

    public function modifyTab(string $key, Closure $modifier): void
    {
        if (isset($this->tabs[$key])) {
            $this->tabs[$key] = $modifier($this->tabs[$key]);
        }
    }

    public function hasTab(string $key): bool
    {
        return isset($this->tabs[$key]);
    }

    // ─────────────────────────────────────────
    // Sub-tab management
    // ─────────────────────────────────────────

    /**
     * Register a sub-tab. The sub-tab must declare its parent via getParentTabKey().
     */
    public function registerSubTab(SettingsSubTab $subTab): void
    {
        $key = $subTab->getParentTabKey() . '.' . $subTab->getKey();

        if (isset($this->subTabs[$key])) {
            return;
        }

        $this->subTabs[$key] = $subTab;
    }

    public function removeSubTab(string $parentKey, string $key): void
    {
        unset($this->subTabs[$parentKey . '.' . $key]);
    }

    // ─────────────────────────────────────────
    // Resolution
    // ─────────────────────────────────────────

    /**
     * Return all tabs sorted by their sort value.
     *
     * @return array<SettingsTab>
     */
    public function getResolvedTabs(): array
    {
        // Attach sub-tabs to their parent tabs
        foreach ($this->subTabs as $subTab) {
            $parentKey = $subTab->getParentTabKey();

            if (isset($this->tabs[$parentKey])) {
                $this->tabs[$parentKey]->addSubTab($subTab);
            }
        }

        // Sort and return
        $tabs = array_values($this->tabs);

        usort($tabs, fn (SettingsTab $a, SettingsTab $b): int => $a->getSort() <=> $b->getSort());

        return $tabs;
    }

    /**
     * External packages can call this in their ServiceProvider boot() to inject tabs.
     */
    public function register(SettingsTab $tab): static
    {
        $this->registerTab($tab, force: false);

        return $this;
    }

    /**
     * External packages can call this in their ServiceProvider boot() to inject sub-tabs.
     */
    public function registerSubTabForParent(string $parentKey, SettingsSubTab $subTab): static
    {
        $subTab->setParentTabKey($parentKey);
        $this->registerSubTab($subTab);

        return $this;
    }

    /**
     * Reset registry (useful for testing).
     */
    public function flush(): void
    {
        $this->tabs = [];
        $this->subTabs = [];
    }
}
