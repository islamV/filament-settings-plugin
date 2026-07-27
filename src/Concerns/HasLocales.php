<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Concerns;

/**
 * Adds locale configuration support to tabs and sub-tabs.
 *
 * Locales can be simple string labels:
 *   ['ar' => 'Arabic', 'en' => 'English']
 *
 * Or rich metadata arrays:
 *   ['ar' => ['label' => 'Arabic', 'direction' => 'rtl'], ...]
 */
trait HasLocales
{
    /** @var array<string, mixed>|null */
    protected ?array $locales = null;

    /**
     * Override locales for this tab/sub-tab specifically.
     *
     * @param  array<string, mixed>  $locales
     */
    public function locales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * Get resolved locales.
     * Falls back to plugin-level locales.
     *
     * @return array<string, mixed>
     */
    public function getLocales(): array
    {
        if ($this->locales !== null) {
            return $this->locales;
        }

        // Fall back to plugin-level locales
        try {
            $plugin = \Islamv\FilamentSettingsPlugin\FilamentSettingsPlugin::get();

            return $plugin->getLocales();
        } catch (\Throwable) {
            // Plugin not registered yet (e.g., during tests or early boot)
            /** @var array<string, mixed> */
            return config('filament-settings.locales', []);
        }
    }

    /**
     * Get locale label string.
     */
    public function getLocaleLabel(string $code): string
    {
        $locales = $this->getLocales();
        $locale  = $locales[$code] ?? $code;

        if (is_array($locale)) {
            return $locale['label'] ?? $code;
        }

        return $locale;
    }

    /**
     * Get text direction for a locale code.
     */
    public function getLocaleDirection(string $code): string
    {
        $locales = $this->getLocales();
        $locale  = $locales[$code] ?? null;

        if (is_array($locale)) {
            return $locale['direction'] ?? 'ltr';
        }

        return 'ltr';
    }

    public function hasLocales(): bool
    {
        return ! empty($this->getLocales());
    }
}
