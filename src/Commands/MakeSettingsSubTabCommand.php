<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeSettingsSubTabCommand extends Command
{
    protected $signature = 'make:settings-sub-tab
        {name : The sub-tab class name (e.g., Stripe)}
        {--parent= : The parent tab key (e.g., payment)}
        {--sort=10 : The sub-tab sort order}
        {--icon=heroicon-o-square-3-stack-3d : The Heroicon identifier}
        {--translatable : Make this sub-tab translatable (adds locale tabs)}
        {--no-settings : Skip creating the Spatie Settings class}
        {--no-migration : Skip creating the settings migration}';

    protected $description = 'Create a new Settings sub-tab under a parent tab';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! is_string($name)) {
            $this->error('Please provide a valid sub-tab name.');

            return self::FAILURE;
        }

        $name      = Str::studly($name);
        $parentKey = (string) ($this->option('parent') ?? '');

        if (empty($parentKey)) {
            $this->error('The --parent option is required. Example: --parent=payment');

            return self::FAILURE;
        }

        $parentKey = Str::kebab($parentKey);
        $parentDir = Str::studly(str_replace('-', ' ', $parentKey));

        $this->createSubTabClass($name, $parentKey, $parentDir);

        if (! $this->option('no-settings')) {
            $this->createSettingsClass($name, $parentKey);
        }

        if (! $this->option('no-settings') && ! $this->option('no-migration')) {
            $this->createSettingsMigration($name, $parentKey);
        }

        $this->info('');
        $this->info('✅ Settings sub-tab created successfully!');
        $this->info('');
        $this->components->bulletList([
            "app/Filament/Settings/Tabs/{$parentDir}/{$name}SettingsSubTab.php",
            "app/Settings/{$name}Settings.php",
        ]);
        $this->info('');
        $this->components->info('The sub-tab will be automatically discovered under the ' . $parentKey . ' tab.');

        return self::SUCCESS;
    }

    protected function createSubTabClass(string $name, string $parentKey, string $parentDir): void
    {
        $directory = app_path("Filament/Settings/Tabs/{$parentDir}");

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        $path = "{$directory}/{$name}SettingsSubTab.php";

        if (file_exists($path)) {
            $this->components->warn("{$name}SettingsSubTab.php already exists. Skipping.");

            return;
        }

        $key          = Str::kebab(Str::snake($name));
        $sort         = (int) ($this->option('sort') ?? 10);
        $icon         = (string) ($this->option('icon') ?? 'heroicon-o-square-3-stack-3d');
        $translatable = $this->option('translatable') ? 'true' : 'false';
        $settingsClass = 'App\\Settings\\' . $name . 'Settings';

        $stub = $this->getStub($name, $key, $parentKey, $sort, $icon, $translatable, $settingsClass);

        file_put_contents($path, $stub);

        $this->components->info("Created sub-tab: {$path}");
    }

    protected function createSettingsClass(string $name, string $parentKey): void
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

        $group = Str::snake($parentKey) . '_' . Str::snake($name);
        $stub  = <<<PHP
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

        file_put_contents($settingsPath, $stub);

        $this->components->info("Created settings: {$settingsPath}");
    }

    protected function createSettingsMigration(string $name, string $parentKey): void
    {
        $migrationsDirectory = database_path('settings');

        if (! is_dir($migrationsDirectory)) {
            mkdir($migrationsDirectory, recursive: true);
        }

        $timestamp = date('Y_m_d_His');
        $group     = Str::snake($parentKey) . '_' . Str::snake($name);
        $path      = $migrationsDirectory . '/' . $timestamp . '_create_' . $group . '_settings.php';

        $stub = <<<PHP
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

        file_put_contents($path, $stub);

        $this->components->info("Created migration: {$path}");
    }

    protected function getStub(
        string $name,
        string $key,
        string $parentKey,
        int $sort,
        string $icon,
        string $translatable,
        string $settingsClass,
    ): string {
        $parentDir = Str::studly(str_replace('-', ' ', $parentKey));

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Filament\Settings\Tabs\\{$parentDir};

use Filament\Forms\Components\TextInput;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use {$settingsClass};

class {$name}SettingsSubTab extends SettingsSubTab
{
    protected int \$sort = {$sort};

    protected bool \$translatable = {$translatable};

    public function getKey(): string
    {
        return '{$key}';
    }

    public function getLabel(): string
    {
        return '{$name}';
    }

    public function getIcon(): string
    {
        return '{$icon}';
    }

    public static function getParentTabKeyStatic(): string
    {
        return '{$parentKey}';
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
}
