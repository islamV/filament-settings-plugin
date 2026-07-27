<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tabs;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Islamv\FilamentSettingsPlugin\Concerns\HasLocales;
use Islamv\FilamentSettingsPlugin\Concerns\HasSort;

/**
 * A sub-tab within a main SettingsTab.
 *
 * Sub-tabs can be:
 *   - Regular (own schema + settings class)
 *   - Translatable (renders locale tabs inside)
 */
abstract class SettingsSubTab
{
    use HasLocales;
    use HasSort;

    protected string $parentTabKey = '';

    protected bool $translatable = false;

    protected ?Closure $visibleUsing = null;

    protected ?Closure $authorizeUsing = null;

    protected ?Closure $beforeSave = null;

    protected ?Closure $afterSave = null;

    protected ?string $permissionKey = null;

    // ─────────────────────────────────────────
    // Required overrides
    // ─────────────────────────────────────────

    abstract public function getKey(): string;

    abstract public function getLabel(): string;

    // ─────────────────────────────────────────
    // Optional overrides
    // ─────────────────────────────────────────

    public function getSettingsClass(): ?string
    {
        return null;
    }

    /**
     * @return array<\Filament\Schemas\Components\Component|\Filament\Forms\Components\Field>
     */
    public function schema(): array
    {
        return [];
    }

    public function getIcon(): null|string|Htmlable|\BackedEnum
    {
        return null;
    }

    /**
     * Declare the parent tab key from within the class (for auto-discovery).
     */
    public static function getParentTabKeyStatic(): ?string
    {
        return null;
    }

    // ─────────────────────────────────────────
    // Static factory
    // ─────────────────────────────────────────

    public static function make(): static
    {
        return app(static::class);
    }

    // ─────────────────────────────────────────
    // Fluent API
    // ─────────────────────────────────────────

    public function translatable(bool $enabled = true): static
    {
        $this->translatable = $enabled;

        return $this;
    }

    public function visible(bool|Closure $condition): static
    {
        $this->visibleUsing = $condition instanceof Closure
            ? $condition
            : fn () => $condition;

        return $this;
    }

    public function authorize(Closure $callback): static
    {
        $this->authorizeUsing = $callback;

        return $this;
    }

    public function permission(string $key): static
    {
        $this->permissionKey = $key;

        return $this;
    }

    public function beforeSave(Closure $callback): static
    {
        $this->beforeSave = $callback;

        return $this;
    }

    public function afterSave(Closure $callback): static
    {
        $this->afterSave = $callback;

        return $this;
    }

    // ─────────────────────────────────────────
    // Internal setters
    // ─────────────────────────────────────────

    public function setParentTabKey(string $key): void
    {
        $this->parentTabKey = $key;
    }

    // ─────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────

    public function getParentTabKey(): string
    {
        if ($this->parentTabKey !== '') {
            return $this->parentTabKey;
        }

        // Try static declaration
        $static = static::getParentTabKeyStatic();

        if ($static !== null) {
            return $static;
        }

        return '';
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function isVisible(): bool
    {
        if ($this->visibleUsing !== null) {
            return (bool) ($this->visibleUsing)();
        }

        return true;
    }

    public function isAuthorized(): bool
    {
        if ($this->authorizeUsing !== null) {
            if (! (bool) ($this->authorizeUsing)()) {
                return false;
            }
        }

        if ($this->permissionKey !== null) {
            $user = filament()->auth()?->user();

            if ($user && method_exists($user, 'can')) {
                return $user->can($this->permissionKey);
            }
        }

        return true;
    }

    public function isAccessible(): bool
    {
        return $this->isVisible() && $this->isAuthorized();
    }

    public function getPermissionKey(): ?string
    {
        if ($this->permissionKey !== null) {
            return $this->permissionKey;
        }

        // Auto-derive: e.g., parent=static-pages, key=privacy-policy → "page_settings_static_pages_privacy_policy"
        $parent = str_replace('-', '_', $this->getParentTabKey());
        $own    = str_replace('-', '_', $this->getKey());

        return "page_settings_{$parent}_{$own}";
    }

    /**
     * Load settings into form state.
     *
     * @return array<string, mixed>
     */
    public function loadSettings(): array
    {
        $settingsClass = $this->getSettingsClass();

        if ($settingsClass === null) {
            return [];
        }

        /** @var \Spatie\LaravelSettings\Settings $settings */
        $settings = app($settingsClass);

        $data = [];

        foreach (get_object_vars($settings) as $property => $value) {
            $data[$property] = $value;
        }

        return $data;
    }

    /**
     * Save form state back to settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveSettings(array $data): void
    {
        $settingsClass = $this->getSettingsClass();

        if ($settingsClass === null) {
            return;
        }

        if ($this->beforeSave !== null) {
            $data = ($this->beforeSave)($data) ?? $data;
        }

        /** @var \Spatie\LaravelSettings\Settings $settings */
        $settings = app($settingsClass);

        foreach (get_object_vars($settings) as $property => $_) {
            if (array_key_exists($property, $data)) {
                $settings->{$property} = $data[$property];
            }
        }

        $settings->save();

        if ($this->afterSave !== null) {
            ($this->afterSave)($data);
        }
    }
}
