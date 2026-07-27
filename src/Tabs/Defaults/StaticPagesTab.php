<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tabs\Defaults;

use Filament\Support\Icons\Heroicon;
use Islamv\FilamentSettingsPlugin\StaticPages\AboutSubTab;
use Islamv\FilamentSettingsPlugin\StaticPages\PrivacyPolicySubTab;
use Islamv\FilamentSettingsPlugin\StaticPages\TermsSubTab;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;

/**
 * Static Pages main tab.
 *
 * Contains built-in sub-tabs: Privacy Policy, Terms, About.
 * Applications can add more sub-tabs via:
 *   SettingsPlugin::make()->subTab('static-pages', ContactUsSubTab::make())
 */
class StaticPagesTab extends SettingsTab
{
    protected int $sort = 30;

    public function __construct()
    {
        // Register built-in static page sub-tabs
        $this->addSubTab(app(PrivacyPolicySubTab::class));
        $this->addSubTab(app(TermsSubTab::class));
        $this->addSubTab(app(AboutSubTab::class));
    }

    public function getKey(): string
    {
        return 'static-pages';
    }

    public function getLabel(): string
    {
        return __('filament-settings::tabs.static_pages.label');
    }

    public function getIcon(): string|\BackedEnum
    {
        return Heroicon::OutlinedDocumentText;
    }

    // No top-level settings class — sub-tabs manage their own settings
    public function getSettingsClass(): ?string
    {
        return null;
    }

    public function schema(): array
    {
        return [];
    }
}
