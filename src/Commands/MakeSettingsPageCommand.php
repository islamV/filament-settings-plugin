<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Convenience command to create a new static page sub-tab under the built-in Static Pages tab.
 *
 * Equivalent to:
 *   make:settings-sub-tab ContactUs --parent=static-pages --translatable
 */
class MakeSettingsPageCommand extends Command
{
    protected $signature = 'make:settings-page
        {name : The page name (e.g., ContactUs, RefundPolicy)}
        {--sort=40 : The sub-tab sort order}
        {--icon=heroicon-o-document-text : The Heroicon identifier}
        {--no-translations : Skip locale/translation tabs for this page}
        {--no-migration : Skip creating the settings migration}';

    protected $description = 'Create a new static page sub-tab under the built-in Static Pages tab';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! is_string($name)) {
            $this->error('Please provide a valid page name.');

            return self::FAILURE;
        }

        $name         = Str::studly($name);
        $key          = Str::kebab(Str::snake($name));
        $sort         = (int) ($this->option('sort') ?? 40);
        $icon         = (string) ($this->option('icon') ?? 'heroicon-o-document-text');
        $translatable = ! $this->option('no-translations');

        $this->createSubTabClass($name, $key, $sort, $icon, $translatable);
        $this->createSettingsClass($name);

        if (! $this->option('no-migration')) {
            $this->createSettingsMigration($name);
        }

        $this->info('');
        $this->info('✅ Static page created successfully!');
        $this->info('');
        $this->components->bulletList([
            "app/Filament/Settings/Tabs/StaticPages/{$name}SubTab.php",
            "app/Settings/{$name}Settings.php",
        ]);
        $this->info('');
        $this->components->info('The page will automatically appear under Static Pages → ' . Str::headline($name) . '.');

        return self::SUCCESS;
    }

    protected function createSubTabClass(string $name, string $key, int $sort, string $icon, bool $translatable): void
    {
        $directory = app_path('Filament/Settings/Tabs/StaticPages');

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        $path = "{$directory}/{$name}SubTab.php";

        if (file_exists($path)) {
            $this->components->warn("{$name}SubTab.php already exists. Skipping.");

            return;
        }

        $translatableStr = $translatable ? 'true' : 'false';
        $settingsClass   = 'App\\Settings\\' . $name . 'Settings';

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Filament\Settings\Tabs\StaticPages;

use Filament\Forms\Components\RichEditor;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use {$settingsClass};

class {$name}SubTab extends SettingsSubTab
{
    protected int \$sort = {$sort};

    protected bool \$translatable = {$translatableStr};

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
        return 'static-pages';
    }

    public function getSettingsClass(): string
    {
        return {$name}Settings::class;
    }

    public function schema(): array
    {
        return [
            RichEditor::make('content')
                ->label('Content')
                ->fileAttachmentsDisk(config('filament-settings.uploads.disk', 'public'))
                ->fileAttachmentsVisibility('public')
                ->columnSpanFull(),
        ];
    }

    public function loadSettings(): array
    {
        /** @var {$name}Settings \$settings */
        \$settings = app({$name}Settings::class);

        return ['content' => \$settings->content];
    }

    public function saveSettings(array \$data): void
    {
        /** @var {$name}Settings \$settings */
        \$settings = app({$name}Settings::class);
        \$settings->content = \$data['content'] ?? [];
        \$settings->save();
    }
}
PHP;

        file_put_contents($path, $stub);

        $this->components->info("Created sub-tab: {$path}");
    }

    protected function createSettingsClass(string $name): void
    {
        $settingsDirectory = app_path('Settings');

        if (! is_dir($settingsDirectory)) {
            mkdir($settingsDirectory, recursive: true);
        }

        $path = "{$settingsDirectory}/{$name}Settings.php";

        if (file_exists($path)) {
            $this->components->warn("{$name}Settings.php already exists. Skipping.");

            return;
        }

        $group = 'static_pages_' . Str::snake($name);

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class {$name}Settings extends Settings
{
    /** @var array */
    public array \$content = [];

    public static function group(): string
    {
        return '{$group}';
    }
}
PHP;

        file_put_contents($path, $stub);

        $this->components->info("Created settings: {$path}");
    }

    protected function createSettingsMigration(string $name): void
    {
        $migrationsDirectory = database_path('settings');

        if (! is_dir($migrationsDirectory)) {
            mkdir($migrationsDirectory, recursive: true);
        }

        $timestamp = date('Y_m_d_His');
        $group     = 'static_pages_' . Str::snake($name);
        $path      = "{$migrationsDirectory}/{$timestamp}_create_{$group}_settings.php";

        $stub = <<<PHP
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        \$this->migrator->add('{$group}.content', []);
    }

    public function down(): void
    {
        \$this->migrator->delete('{$group}.content');
    }
};
PHP;

        file_put_contents($path, $stub);

        $this->components->info("Created migration: {$path}");
    }
}
