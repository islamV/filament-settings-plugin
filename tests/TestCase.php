<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tests;

use Filament\FilamentServiceProvider;
use Islamv\FilamentSettingsPlugin\FilamentSettingsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            LaravelSettingsServiceProvider::class,
            FilamentSettingsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('filament-settings.locales', [
            'en' => ['label' => 'English', 'direction' => 'ltr'],
            'ar' => ['label' => 'Arabic',  'direction' => 'rtl'],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
