<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Islamv\FilamentSettingsPlugin\Discovery\TabDiscovery;
use Islamv\FilamentSettingsPlugin\Pages\Settings;
use Islamv\FilamentSettingsPlugin\Registry\SettingsRegistry;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;

class FilamentSettingsPlugin implements Plugin
{
    protected bool $useShield = false;

    protected bool $withDefaultTabs = true;

    /** @var array<string> */
    protected array $removedTabs = [];

    /** @var array<string, SettingsTab> */
    protected array $replacedTabs = [];

    /** @var array<string, Closure> */
    protected array $tabModifiers = [];

    /** @var array<SettingsTab> */
    protected array $manualTabs = [];

    /** @var array<string, array<SettingsSubTab>> */
    protected array $manualSubTabs = [];

    /** @var array<string, string|array{label: string, direction: string}> */
    protected array $locales = [];

    protected bool $discoveryEnabled = true;

    protected ?string $discoveryPath = null;

    protected ?string $discoveryNamespace = null;

    public function getId(): string
    {
        return 'filament-settings';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Settings::class]);
    }

    public function boot(Panel $panel): void
    {
        $this->resolveAndRegisterTabs();
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(app(static::class)->getId());
    }

    // ─────────────────────────────────────────
    // Fluent API
    // ─────────────────────────────────────────

    public function useShield(bool $enabled = true): static
    {
        $this->useShield = $enabled;

        return $this;
    }

    public function withoutDefaultTabs(): static
    {
        $this->withDefaultTabs = false;

        return $this;
    }

    public function removeTab(string $key): static
    {
        $this->removedTabs[] = $key;

        return $this;
    }

    public function replaceTab(string $key, SettingsTab $tab): static
    {
        $this->replacedTabs[$key] = $tab;

        return $this;
    }

    public function modifyTab(string $key, Closure $modifier): static
    {
        $this->tabModifiers[$key] = $modifier;

        return $this;
    }

    public function tab(SettingsTab $tab): static
    {
        $this->manualTabs[] = $tab;

        return $this;
    }

    /**
     * @param  array<SettingsTab>  $tabs
     */
    public function tabs(array $tabs): static
    {
        foreach ($tabs as $tab) {
            $this->manualTabs[] = $tab;
        }

        return $this;
    }

    public function subTab(string $parentKey, SettingsSubTab $subTab): static
    {
        $this->manualSubTabs[$parentKey][] = $subTab;

        return $this;
    }

    /**
     * @param  array<string, string|array{label: string, direction: string}>  $locales
     */
    public function locales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function discoverTabs(bool|string $pathOrEnabled = true, ?string $namespace = null): static
    {
        if ($pathOrEnabled === false) {
            $this->discoveryEnabled = false;

            return $this;
        }

        if (is_string($pathOrEnabled)) {
            $this->discoveryPath = $pathOrEnabled;
            $this->discoveryNamespace = $namespace;
        }

        $this->discoveryEnabled = true;

        return $this;
    }

    // ─────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────

    public function isShieldEnabled(): bool
    {
        return $this->useShield;
    }

    /**
     * @return array<string, string|array{label: string, direction: string}>
     */
    public function getLocales(): array
    {
        if (! empty($this->locales)) {
            return $this->locales;
        }

        /** @var array<string, string|array{label: string, direction: string}> */
        $configLocales = config('filament-settings.locales', []);

        return $configLocales;
    }

    // ─────────────────────────────────────────
    // Internal resolution
    // ─────────────────────────────────────────

    protected function resolveAndRegisterTabs(): void
    {
        /** @var SettingsRegistry $registry */
        $registry = app(SettingsRegistry::class);

        // 1. Default tabs (unless disabled)
        if ($this->withDefaultTabs) {
            foreach ($this->resolveDefaultTabs() as $tab) {
                $registry->registerTab($tab);
            }
        }

        // 2. Remove tabs
        foreach ($this->removedTabs as $key) {
            $registry->removeTab($key);
        }

        // 3. Replace tabs
        foreach ($this->replacedTabs as $key => $replacement) {
            $registry->replaceTab($key, $replacement);
        }

        // 4. Discover application tabs
        if ($this->discoveryEnabled) {
            $discovery = new TabDiscovery(
                path: $this->discoveryPath ?? app_path('Filament/Settings/Tabs'),
                namespace: $this->discoveryNamespace ?? 'App\\Filament\\Settings\\Tabs',
            );

            foreach ($discovery->discoverTabs() as $tab) {
                $registry->registerTab($tab);
            }

            foreach ($discovery->discoverSubTabs() as $subTab) {
                $registry->registerSubTab($subTab);
            }
        }

        // 5. Manual tabs
        foreach ($this->manualTabs as $tab) {
            $registry->registerTab($tab);
        }

        // 6. Manual sub-tabs
        foreach ($this->manualSubTabs as $parentKey => $subTabs) {
            foreach ($subTabs as $subTab) {
                $subTab->setParentTabKey($parentKey);
                $registry->registerSubTab($subTab);
            }
        }

        // 7. Apply tab modifiers
        foreach ($this->tabModifiers as $key => $modifier) {
            $registry->modifyTab($key, $modifier);
        }

        // 8. Validate Shield if enabled
        if ($this->useShield) {
            $this->assertShieldAvailable();
        }
    }

    /**
     * @return array<SettingsTab>
     */
    protected function resolveDefaultTabs(): array
    {
        return [
            app(\Islamv\FilamentSettingsPlugin\Tabs\Defaults\GeneralSettingsTab::class),
            app(\Islamv\FilamentSettingsPlugin\Tabs\Defaults\SocialLinksTab::class),
            app(\Islamv\FilamentSettingsPlugin\Tabs\Defaults\StaticPagesTab::class),
        ];
    }

    protected function assertShieldAvailable(): void
    {
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::class)) {
            throw new \RuntimeException(
                'Filament Shield integration is enabled, but bezhansalleh/filament-shield is not installed. '
                . 'Run: composer require bezhansalleh/filament-shield'
            );
        }
    }
}
