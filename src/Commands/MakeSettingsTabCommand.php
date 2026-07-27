<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class MakeSettingsTabCommand extends Command
{
    protected $signature = 'make:settings-tab
        {name : The tab class name (e.g., Payment)}
        {--sort=40 : The tab sort order}
        {--icon=heroicon-o-squares-2x2 : The Heroicon identifier}
        {--no-settings : Skip creating the Spatie Settings class}
        {--no-migration : Skip creating the settings migration}';

    protected $description = 'Create a new Settings tab with its Spatie Settings class and migration';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! is_string($name)) {
            $this->error('Please provide a valid tab name.');

            return self::FAILURE;
        }

        $name = Str::studly($name);

        $this->createTabClass($name);

        if (! $this->option('no-settings')) {
            $this->createSettingsClass($name);
        }

        if (! $this->option('no-settings') && ! $this->option('no-migration')) {
            $this->createSettingsMigration($name);
        }

        $this->info('');
        $this->info('✅ Settings tab created successfully!');
        $this->info('');
        $this->components->bulletList([
            'app/Filament/Settings/Tabs/' . $name . 'SettingsTab.php',
            'app/Settings/' . $name . 'Settings.php',
            'database/settings/create_' . Str::snake($name) . '_settings.php',
        ]);
        $this->info('');
        $this->components->info('The tab will be automatically discovered. No PanelProvider changes required.');

        return self::SUCCESS;
    }

    protected function createTabClass(string $name): void
    {
        $tabDirectory = app_path('Filament/Settings/Tabs');

        if (! is_dir($tabDirectory)) {
            mkdir($tabDirectory, recursive: true);
        }

        $tabPath = $tabDirectory . '/' . $name . 'SettingsTab.php';

        if (file_exists($tabPath)) {
            $this->components->warn($name . 'SettingsTab.php already exists. Skipping.');

            return;
        }

        $key  = Str::kebab(Str::snake($name));
        $sort = (int) ($this->option('sort') ?? 40);
        $icon = (string) ($this->option('icon') ?? 'heroicon-o-squares-2x2');

        $stub = $this->getTabStub($name, $key, $sort, $icon);

        file_put_contents($tabPath, $stub);

        $this->components->info('Created tab: ' . $tabPath);
    }

    protected function createSettingsClass(string $name): void
    {
        $settingsDirectory = app_path('Settings');

        if (! is_dir($settingsDirectory)) {
            mkdir($settingsDirectory, recursive: true);
        }

        $settingsPath = $settingsDirectory . '/' . $name . 'Settings.php';

        if (file_exists($settingsPath)) {
            $this->components->warn($name . 'Settings.php already exists. Skipping.');

            return;
        }

        $group = Str::snake($name);
        $stub  = $this->getSettingsStub($name, $group);

        file_put_contents($settingsPath, $stub);

        $this->components->info('Created settings: ' . $settingsPath);
    }

    protected function createSettingsMigration(string $name): void
    {
        $migrationsDirectory = database_path('settings');

        if (! is_dir($migrationsDirectory)) {
            mkdir($migrationsDirectory, recursive: true);
        }

        $timestamp    = date('Y_m_d_His');
        $snakeName    = Str::snake($name);
        $migrationPath = $migrationsDirectory . '/' . $timestamp . '_create_' . $snakeName . '_settings.php';

        $stub = $this->getMigrationStub($name, $snakeName);

        file_put_contents($migrationPath, $stub);

        $this->components->info('Created migration: ' . $migrationPath);
    }

    protected function getTabStub(string $name, string $key, int $sort, string $icon): string
    {
        $settingsClass = 'App\\Settings\\' . $name . 'Settings';

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Filament\Settings\Tabs;

use Filament\Forms\Components\TextInput;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;
use {$settingsClass};

class {$name}SettingsTab extends SettingsTab
{
    protected int \$sort = {$sort};

    public function getKey(): string
    {
        return '{$key}';
    }

    public function getLabel(): string
    {
        return __('{$key}.title') !== '{$key}.title' ? __('{$key}.title') : '{$name}';
    }

    public function getIcon(): string
    {
        return '{$icon}';
    }

    public function getSettingsClass(): string
    {
        return {$name}Settings::class;
    }

    public function schema(): array
    {
        return [
            // Add your Filament form fields here
        ];
    }
}
PHP;
    }

    protected function getSettingsStub(string $name, string $group): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class {$name}Settings extends Settings
{
    // Define your settings properties here
    // public string \$example = '';

    public static function group(): string
    {
        return '{$group}';
    }
}
PHP;
    }

    protected function getMigrationStub(string $name, string $group): string
    {
        return <<<PHP
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        \$this->migrator->add('{$group}.example', '');
    }

    public function down(): void
    {
        \$this->migrator->delete('{$group}.example');
    }
};
PHP;
    }
}
