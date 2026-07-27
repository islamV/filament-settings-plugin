<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin;

use Islamv\FilamentSettingsPlugin\Commands\MakeSettingsPageCommand;
use Islamv\FilamentSettingsPlugin\Commands\MakeSettingsSubTabCommand;
use Islamv\FilamentSettingsPlugin\Commands\MakeSettingsTabCommand;
use Islamv\FilamentSettingsPlugin\Registry\SettingsRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentSettingsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-settings';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('filament-settings')
            ->hasTranslations()
            ->hasViews()
            ->hasCommands([
                MakeSettingsTabCommand::class,
                MakeSettingsSubTabCommand::class,
                MakeSettingsPageCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SettingsRegistry::class, fn () => new SettingsRegistry);
    }

    public function packageBooted(): void
    {
        //
    }
}
