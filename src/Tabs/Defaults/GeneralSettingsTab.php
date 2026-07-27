<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tabs\Defaults;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Islamv\FilamentSettingsPlugin\Settings\GeneralSettings;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;

class GeneralSettingsTab extends SettingsTab
{
    protected int $sort = 10;

    public function getKey(): string
    {
        return 'general';
    }

    public function getLabel(): string
    {
        return __('filament-settings::tabs.general.label');
    }

    public function getIcon(): string|\BackedEnum
    {
        return Heroicon::OutlinedCog6Tooth;
    }

    public function getSettingsClass(): string
    {
        return GeneralSettings::class;
    }

    public function schema(): array
    {
        return [
            TextInput::make('app_name')
                ->label(__('filament-settings::tabs.general.fields.app_name'))
                ->required()
                ->maxLength(255)
                ->columnSpan(1),

            TextInput::make('support_email')
                ->label(__('filament-settings::tabs.general.fields.support_email'))
                ->email()
                ->maxLength(255)
                ->columnSpan(1),

            TextInput::make('support_phone')
                ->label(__('filament-settings::tabs.general.fields.support_phone'))
                ->tel()
                ->maxLength(50)
                ->columnSpan(1),

            Textarea::make('app_description')
                ->label(__('filament-settings::tabs.general.fields.app_description'))
                ->rows(3)
                ->columnSpanFull(),

            FileUpload::make('logo')
                ->label(__('filament-settings::tabs.general.fields.logo'))
                ->image()
                ->disk(config('filament-settings.uploads.disk', 'public'))
                ->directory(config('filament-settings.uploads.directory', 'settings'))
                ->visibility('public')
                ->columnSpan(1),

            FileUpload::make('favicon')
                ->label(__('filament-settings::tabs.general.fields.favicon'))
                ->image()
                ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml'])
                ->disk(config('filament-settings.uploads.disk', 'public'))
                ->directory(config('filament-settings.uploads.directory', 'settings'))
                ->visibility('public')
                ->columnSpan(1),
        ];
    }
}
