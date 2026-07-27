<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\StaticPages;

use Filament\Forms\Components\RichEditor;
use Filament\Support\Icons\Heroicon;
use Islamv\FilamentSettingsPlugin\Settings\TermsSettings;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;

class TermsSubTab extends SettingsSubTab
{
    protected int $sort = 20;

    protected bool $translatable = true;

    public function getKey(): string
    {
        return 'terms';
    }

    public function getLabel(): string
    {
        return __('filament-settings::tabs.static_pages.terms');
    }

    public function getIcon(): string|\BackedEnum
    {
        return Heroicon::OutlinedScale;
    }

    public static function getParentTabKeyStatic(): string
    {
        return 'static-pages';
    }

    public function getSettingsClass(): string
    {
        return TermsSettings::class;
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
        /** @var TermsSettings $settings */
        $settings = app(TermsSettings::class);

        return ['content' => $settings->content];
    }

    public function saveSettings(array $data): void
    {
        /** @var TermsSettings $settings */
        $settings = app(TermsSettings::class);
        $settings->content = $data['content'] ?? [];
        $settings->save();
    }
}
