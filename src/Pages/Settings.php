<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Islamv\FilamentSettingsPlugin\Authorization\SettingsPermissionResolver;
use Islamv\FilamentSettingsPlugin\Registry\SettingsRegistry;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;

/**
 * The single Settings Filament Page.
 *
 * Renders ONE navigation entry containing all registered tabs.
 * Tab hierarchy: Main Tab → Sub-Tab → Locale Tab (for translatable sub-tabs).
 *
 * State strategy:
 * - Each tab's form data is namespaced: tabKey__fieldName
 * - Each sub-tab's data: tabKey__subTabKey__fieldName
 * - Translatable locale data: tabKey__subTabKey__content.{locale}
 * - Save actions are scoped — only saves the relevant Settings class.
 */
class Settings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    /** @var array<string, mixed> */
    public ?array $data = [];

    // ─────────────────────────────────────────
    // Navigation / Page meta
    // ─────────────────────────────────────────

    public static function getNavigationLabel(): string
    {
        /** @var string */
        return config('filament-settings.navigation.label', __('filament-settings::navigation.label'));
    }

    public static function getNavigationGroup(): ?string
    {
        /** @var string|null */
        return config('filament-settings.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        /** @var int */
        return (int) config('filament-settings.navigation.sort', 100);
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return Heroicon::OutlinedCog6Tooth;
    }

    public function getTitle(): string
    {
        return __('filament-settings::navigation.title');
    }

    public function getHeading(): string
    {
        return __('filament-settings::navigation.title');
    }

    // ─────────────────────────────────────────
    // Authorization
    // ─────────────────────────────────────────

    public static function canAccess(): bool
    {
        /** @var SettingsPermissionResolver $resolver */
        $resolver = app(SettingsPermissionResolver::class);

        return $resolver->canAccessSettingsPage();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // ─────────────────────────────────────────
    // Mount — load all accessible settings
    // ─────────────────────────────────────────

    public function mount(): void
    {
        $formData = [];

        foreach ($this->getAccessibleTabs() as $tab) {
            if ($tab->hasSubTabs()) {
                foreach ($tab->getSubTabs() as $subTab) {
                    if (! $subTab->isAccessible()) {
                        continue;
                    }

                    $subSettings = $subTab->loadSettings();

                    if ($subTab->isTranslatable()) {
                        // content is ['ar' => '...', 'en' => '...']
                        $content = $subSettings['content'] ?? [];
                        foreach ($content as $locale => $value) {
                            $formData[$tab->getKey() . '__' . $subTab->getKey() . '__content.' . $locale] = $value;
                        }
                        // Also fill empty slots for configured locales
                        foreach ($subTab->getLocales() as $locale => $_) {
                            $stateKey = $tab->getKey() . '__' . $subTab->getKey() . '__content.' . $locale;
                            if (! isset($formData[$stateKey])) {
                                $formData[$stateKey] = '';
                            }
                        }
                    } else {
                        foreach ($subSettings as $key => $value) {
                            $formData[$tab->getKey() . '__' . $subTab->getKey() . '__' . $key] = $value;
                        }
                    }
                }
            } else {
                $tabSettings = $tab->loadSettings();
                foreach ($tabSettings as $key => $value) {
                    $formData[$tab->getKey() . '__' . $key] = $value;
                }
            }
        }

        $this->form->fill($formData);
    }

    // ─────────────────────────────────────────
    // Content schema — the full tabs hierarchy
    // ─────────────────────────────────────────

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('settings_tabs')
                ->persistTabInQueryString('tab')
                ->tabs($this->buildMainTabs()),
        ]);
    }

    // ─────────────────────────────────────────
    // Tab builders
    // ─────────────────────────────────────────

    /** @return array<Tab> */
    protected function buildMainTabs(): array
    {
        $tabs = [];

        foreach ($this->getAccessibleTabs() as $settingsTab) {
            $tabs[] = $this->buildMainTab($settingsTab);
        }

        return $tabs;
    }

    protected function buildMainTab(SettingsTab $tab): Tab
    {
        $filamentTab = Tab::make($tab->getLabel())
            ->icon($tab->getIcon());

        if ($tab->getBadge() !== null) {
            $filamentTab->badge($tab->getBadge());
        }

        if ($tab->hasSubTabs()) {
            $filamentTab->schema([
                Tabs::make($tab->getKey() . '__subtabs')
                    ->persistTabInQueryString('subtab')
                    ->tabs($this->buildSubTabs($tab)),
            ]);
        } else {
            // Scope the field names with the tab key prefix via statePath
            $schema = $tab->schema();
            $schema = $this->prefixFieldNames($schema, $tab->getKey() . '__');
            $schema[] = $this->makeSaveAction('save_' . $tab->getKey(), fn () => $this->saveMainTab($tab));
            $filamentTab->schema($schema);
        }

        return $filamentTab;
    }

    /** @return array<Tab> */
    protected function buildSubTabs(SettingsTab $parentTab): array
    {
        $tabs = [];

        foreach ($parentTab->getSubTabs() as $subTab) {
            if (! $subTab->isAccessible()) {
                continue;
            }

            $tabs[] = $this->buildSubTab($parentTab, $subTab);
        }

        return $tabs;
    }

    protected function buildSubTab(SettingsTab $parentTab, SettingsSubTab $subTab): Tab
    {
        $filamentTab = Tab::make($subTab->getLabel())
            ->icon($subTab->getIcon());

        if ($subTab->isTranslatable() && $subTab->hasLocales()) {
            // Render locale tabs
            $filamentTab->schema([
                Tabs::make($parentTab->getKey() . '__' . $subTab->getKey() . '__locales')
                    ->persistTabInQueryString('locale')
                    ->tabs($this->buildLocaleTabs($parentTab, $subTab)),
            ]);
        } else {
            $prefix = $parentTab->getKey() . '__' . $subTab->getKey() . '__';
            $schema = $this->prefixFieldNames($subTab->schema(), $prefix);
            $schema[] = $this->makeSaveAction(
                'save_' . $parentTab->getKey() . '_' . $subTab->getKey(),
                fn () => $this->saveSubTab($parentTab, $subTab)
            );
            $filamentTab->schema($schema);
        }

        return $filamentTab;
    }

    /** @return array<Tab> */
    protected function buildLocaleTabs(SettingsTab $parentTab, SettingsSubTab $subTab): array
    {
        $tabs = [];

        foreach ($subTab->getLocales() as $localeCode => $localeConfig) {
            $label     = is_array($localeConfig) ? ($localeConfig['label'] ?? $localeCode) : $localeConfig;
            $direction = is_array($localeConfig) ? ($localeConfig['direction'] ?? 'ltr') : 'ltr';

            // For each locale, clone the schema with locale-specific field names
            $localizedSchema = $this->buildLocaleSchema(
                $parentTab,
                $subTab,
                $localeCode,
                $direction
            );

            $tabs[] = Tab::make($label)->schema($localizedSchema);
        }

        return $tabs;
    }

    /**
     * Build schema for one locale within a translatable sub-tab.
     * Maps field 'content' → state key 'parentKey__subTabKey__content.{locale}'
     *
     * @return array<\Filament\Schemas\Components\Component|\Filament\Forms\Components\Field>
     */
    protected function buildLocaleSchema(
        SettingsTab $parentTab,
        SettingsSubTab $subTab,
        string $locale,
        string $direction
    ): array {
        $stateKey = $parentTab->getKey() . '__' . $subTab->getKey() . '__content.' . $locale;

        // Build a locale-specific rich editor bound to the correct state key
        $schema = [];

        foreach ($subTab->schema() as $field) {
            // We need a field whose statePath resolves to stateKey
            // The cleanest approach: replace the field with one named exactly stateKey
            $cloned = clone $field;

            if (method_exists($cloned, 'name')) {
                $cloned->name($stateKey);
            }

            $schema[] = $cloned;
        }

        // Save action that saves only this locale
        $schema[] = $this->makeSaveAction(
            'save_' . $parentTab->getKey() . '_' . $subTab->getKey() . '_' . $locale,
            fn () => $this->saveLocale($parentTab, $subTab, $locale)
        );

        return $schema;
    }

    // ─────────────────────────────────────────
    // Save logic
    // ─────────────────────────────────────────

    /**
     * Save a main tab (no sub-tabs).
     */
    public function saveMainTab(SettingsTab $tab): void
    {
        if (! $tab->isAccessible()) {
            $this->notifyUnauthorized();

            return;
        }

        $state  = $this->form->getState();
        $prefix = $tab->getKey() . '__';
        $data   = $this->extractPrefixedData($state, $prefix);

        $tab->saveSettings($data);

        $this->notifySaved();
    }

    /**
     * Save a sub-tab (non-translatable).
     */
    public function saveSubTab(SettingsTab $parentTab, SettingsSubTab $subTab): void
    {
        if (! $parentTab->isAccessible() || ! $subTab->isAccessible()) {
            $this->notifyUnauthorized();

            return;
        }

        $state  = $this->form->getState();
        $prefix = $parentTab->getKey() . '__' . $subTab->getKey() . '__';
        $data   = $this->extractPrefixedData($state, $prefix);

        $subTab->saveSettings($data);

        $this->notifySaved();
    }

    /**
     * Save a single locale within a translatable sub-tab.
     * Merges the locale value into the existing content array.
     */
    public function saveLocale(SettingsTab $parentTab, SettingsSubTab $subTab, string $locale): void
    {
        if (! $parentTab->isAccessible() || ! $subTab->isAccessible()) {
            $this->notifyUnauthorized();

            return;
        }

        $state    = $this->form->getState();
        $stateKey = $parentTab->getKey() . '__' . $subTab->getKey() . '__content.' . $locale;

        // Get current content from settings
        $currentSettings = $subTab->loadSettings();
        $content         = $currentSettings['content'] ?? [];

        // Update only this locale
        $content[$locale] = $state[$stateKey] ?? '';

        $subTab->saveSettings(['content' => $content]);

        $this->notifySaved();
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    /**
     * @return array<SettingsTab>
     */
    protected function getAccessibleTabs(): array
    {
        /** @var SettingsRegistry $registry */
        $registry = app(SettingsRegistry::class);

        return array_values(array_filter(
            $registry->getResolvedTabs(),
            fn (SettingsTab $tab): bool => $tab->isAccessible()
        ));
    }

    /**
     * Prefix all field names in a schema array.
     * Uses statePath scoping via field name since Filament 5 uses schema->statePath().
     *
     * @param  array<mixed>  $schema
     * @return array<mixed>
     */
    protected function prefixFieldNames(array $schema, string $prefix): array
    {
        return array_map(function (mixed $field) use ($prefix): mixed {
            if (is_object($field) && method_exists($field, 'name') && method_exists($field, 'getName')) {
                $originalName = $field->getName();
                if ($originalName && ! str_contains($originalName, '__')) {
                    return $field->name($prefix . $originalName);
                }
            }

            return $field;
        }, $schema);
    }

    /**
     * Extract state keys that start with a prefix, stripping the prefix.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function extractPrefixedData(array $state, string $prefix): array
    {
        $data = [];

        foreach ($state as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $data[substr($key, strlen($prefix))] = $value;
            }
        }

        return $data;
    }

    protected function makeSaveAction(string $name, \Closure $action): SchemaActions
    {
        return SchemaActions::make([
            Action::make($name)
                ->label(__('filament-settings::actions.save'))
                ->action($action),
        ]);
    }

    protected function notifySaved(): void
    {
        Notification::make()
            ->success()
            ->title(__('filament-settings::notifications.saved'))
            ->send();
    }

    protected function notifyUnauthorized(): void
    {
        Notification::make()
            ->danger()
            ->title(__('filament-settings::notifications.unauthorized'))
            ->send();
    }
}
