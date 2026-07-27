<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Discovery;

use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;
use Symfony\Component\Finder\Finder;

/**
 * Scans a directory for SettingsTab and SettingsSubTab classes.
 *
 * Convention:
 *   app/Filament/Settings/Tabs/         → discovers main tabs
 *   app/Filament/Settings/Tabs/{Name}/  → discovers sub-tabs (declared parent via getParentTabKeyStatic())
 */
class TabDiscovery
{
    public function __construct(
        protected string $path,
        protected string $namespace,
    ) {}

    /**
     * Discover all SettingsTab classes in the configured directory.
     *
     * @return array<SettingsTab>
     */
    public function discoverTabs(): array
    {
        if (! is_dir($this->path)) {
            return [];
        }

        $tabs = [];

        try {
            $finder = Finder::create()
                ->files()
                ->name('*.php')
                ->in($this->path)
                ->depth(0); // Only root level = main tabs
        } catch (\InvalidArgumentException) {
            return [];
        }

        foreach ($finder as $file) {
            $class = $this->fileToClass($file->getRealPath());

            if (! $class || ! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, SettingsTab::class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $tabs[] = app($class);
        }

        return $tabs;
    }

    /**
     * Discover all SettingsSubTab classes in subdirectories.
     *
     * Each sub-tab class must implement getParentTabKeyStatic() to declare its parent.
     *
     * @return array<SettingsSubTab>
     */
    public function discoverSubTabs(): array
    {
        if (! is_dir($this->path)) {
            return [];
        }

        $subTabs = [];

        try {
            $finder = Finder::create()
                ->files()
                ->name('*.php')
                ->in($this->path)
                ->depth('>0'); // Subdirectories only = sub-tabs
        } catch (\InvalidArgumentException) {
            return [];
        }

        foreach ($finder as $file) {
            $class = $this->fileToClass($file->getRealPath());

            if (! $class || ! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, SettingsSubTab::class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            /** @var SettingsSubTab $instance */
            $instance = app($class);

            // Must declare a parent via static method or instance method
            $parentKey = $class::getParentTabKeyStatic() ?? $instance->getParentTabKey();

            if (empty($parentKey)) {
                continue; // Skip orphaned sub-tabs
            }

            $instance->setParentTabKey($parentKey);
            $subTabs[] = $instance;
        }

        return $subTabs;
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    protected function fileToClass(string $filePath): ?string
    {
        // Convert file path to namespace
        $relativePath = str_replace($this->path . DIRECTORY_SEPARATOR, '', $filePath);
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
        $relativePath = preg_replace('/\.php$/', '', $relativePath);

        if ($relativePath === null) {
            return null;
        }

        return rtrim($this->namespace, '\\') . '\\' . ltrim($relativePath, '\\');
    }
}
