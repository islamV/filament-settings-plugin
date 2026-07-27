<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tabs;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Islamv\FilamentSettingsPlugin\Concerns\HasLocales;
use Islamv\FilamentSettingsPlugin\Concerns\HasSort;

/**
 * Base class for all main settings tabs.
 *
 * Extend this to create a custom settings tab:
 *
 *   class PaymentSettingsTab extends SettingsTab { ... }
 */
abstract class SettingsTab
{
    use HasLocales;
    use HasSort;

    /** @var array<SettingsSubTab> */
    protected array $subTabs = [];

    /** @var array<SettingsSubTab> Registered separately to avoid duplicates */
    protected array $registeredSubTabKeys = [];

    protected ?Closure $visibleUsing = null;

    protected ?Closure $authorizeUsing = null;

    protected ?Closure $beforeSave = null;

    protected ?Closure $afterSave = null;

    protected ?string $permissionKey = null;

    protected ?string $badge = null;

    protected null|string|Closure $badgeColor = null;

    // ─────────────────────────────────────────
    // Required overrides
    // ─────────────────────────────────────────

    abstract public function getKey(): string;

    abstract public function getLabel(): string;

    /**
     * The Spatie Settings class FQCN, or null if this tab uses sub-tabs only.
     */
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

    // ─────────────────────────────────────────
    // Optional overrides
    // ─────────────────────────────────────────

    public function getIcon(): null|string|Htmlable|\BackedEnum
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

    public function badge(string|Closure|null $badge): static
    {
        $this->badge = $badge instanceof Closure ? $badge() : $badge;

        return $this;
    }

    public function badgeColor(string|Closure|null $color): static
    {
        $this->badgeColor = $color;

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

    public function subTabs(array $subTabs): static
    {
        foreach ($subTabs as $subTab) {
            $this->addSubTab($subTab);
        }

        return $this;
    }

    public function addSubTab(SettingsSubTab $subTab): static
    {
        $key = $subTab->getKey();

        if (in_array($key, $this->registeredSubTabKeys, strict: true)) {
            return $this;
        }

        $this->subTabs[] = $subTab;
        $this->registeredSubTabKeys[] = $key;

        return $this;
    }

    // ─────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────

    public function isVisible(): bool
    {
        if ($this->visibleUsing !== null) {
            return (bool) ($this->visibleUsing)();
        }

        return true;
    }

    public function isAuthorized(): bool
    {
        // Authorization callback
        if ($this->authorizeUsing !== null) {
            if (! (bool) ($this->authorizeUsing)()) {
                return false;
            }
        }

        // Shield permission
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

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getBadgeColor(): ?string
    {
        if ($this->badgeColor instanceof Closure) {
            return ($this->badgeColor)();
        }

        return $this->badgeColor;
    }

    /**
     * @return array<SettingsSubTab>
     */
    public function getSubTabs(): array
    {
        $subTabs = array_values($this->subTabs);

        usort($subTabs, fn (SettingsSubTab $a, SettingsSubTab $b): int => $a->getSort() <=> $b->getSort());

        return $subTabs;
    }

    public function hasSubTabs(): bool
    {
        return ! empty($this->subTabs);
    }

    public function getPermissionKey(): ?string
    {
        if ($this->permissionKey !== null) {
            return $this->permissionKey;
        }

        // Auto-derive from key: e.g., "payment" → "page_settings_payment"
        return 'page_settings_' . str_replace('-', '_', $this->getKey());
    }

    public function callBeforeSave(array $data): array
    {
        if ($this->beforeSave !== null) {
            return ($this->beforeSave)($data) ?? $data;
        }

        return $data;
    }

    public function callAfterSave(array $data): void
    {
        if ($this->afterSave !== null) {
            ($this->afterSave)($data);
        }
    }

    /**
     * Load settings from the Spatie Settings class into form state.
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
     * Save form state back to the Spatie Settings class.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveSettings(array $data): void
    {
        $settingsClass = $this->getSettingsClass();

        if ($settingsClass === null) {
            return;
        }

        $data = $this->callBeforeSave($data);

        /** @var \Spatie\LaravelSettings\Settings $settings */
        $settings = app($settingsClass);

        foreach (get_object_vars($settings) as $property => $_) {
            if (array_key_exists($property, $data)) {
                $settings->{$property} = $data[$property];
            }
        }

        $settings->save();

        $this->callAfterSave($data);
    }
}
