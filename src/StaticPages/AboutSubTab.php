<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\StaticPages;

use Filament\Forms\Components\RichEditor;
use Filament\Support\Icons\Heroicon;
use Islamv\FilamentSettingsPlugin\Settings\AboutSettings;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;

class AboutSubTab extends SettingsSubTab
{
    protected int $sort = 30;

    protected bool $translatable = true;

    public function getKey(): string
    {
        return 'about';
    }

    public function getLabel(): string
    {
        return __('filament-settings::tabs.static_pages.about');
    }

    public function getIcon(): string|\BackedEnum
    {
        return Heroicon::OutlinedInformationCircle;
    }

    public static function getParentTabKeyStatic(): string
    {
        return 'static-pages';
    }

    public function getSettingsClass(): string
    {
        return AboutSettings::class;
    }

    public function schema(): array
    {
        return [
            RichEditor::make('content')
                ->label(__('filament-settings::tabs.static_pages.content'))
                ->fileAttachmentsDisk(config('filament-settings.uploads.disk', 'public'))
                ->fileAttachmentsVisibility('public')
                ->columnSpanFull(),
        ];
    }

    public function loadSettings(): array
    {
        /** @var AboutSettings $settings */
        $settings = app(AboutSettings::class);

        return ['content' => $settings->content];
    }

    public function saveSettings(array $data): void
    {
        /** @var AboutSettings $settings */
        $settings = app(AboutSettings::class);
        $settings->content = $data['content'] ?? [];
        $settings->save();
    }
}
