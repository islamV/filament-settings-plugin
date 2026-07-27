# Filament Settings Plugin

[![Latest Version on Packagist](https://img.shields.io/packagist/v/islamv/filament-settings-plugin.svg?style=flat-square)](https://packagist.org/packages/islamv/filament-settings-plugin)
[![Total Downloads](https://img.shields.io/packagist/dt/islamv/filament-settings-plugin.svg?style=flat-square)](https://packagist.org/packages/islamv/filament-settings-plugin)
[![License](https://img.shields.io/packagist/l/islamv/filament-settings-plugin.svg?style=flat-square)](LICENSE.md)

A production-ready, highly architecture-focused reusable Filament Settings plugin powered by `spatie/laravel-settings`. Built specifically for modern Filament v5 and Laravel applications.

---

## Features

- ⚙️ **Single Filament Settings Page**: Clean, unified navigation page with persisable tabs in URL query strings.
- 🧩 **Built-in Default Tabs**: Includes pre-configured **General Settings**, **Social Links** (with dynamic repeaters), and **Static Pages** (Privacy Policy, Terms, About).
- 🗂️ **Nested Sub-Tabs**: Support for main tabs, nested sub-tabs, and dynamic dynamic child registration.
- 🌐 **Multilingual/Locale Tabs**: Sub-tabs can be marked translatable, automatically rendering tabbed interfaces for each configured locale with RTL support.
- 💾 **Spatie Laravel Settings Integration**: Strictly powered by `spatie/laravel-settings` with type-safe setting classes and database migrations.
- 🛡️ **Optional Shield Integration**: Seamless permission resolution using `bezhansalleh/filament-shield` without hardcoding dependencies.
- 🚀 **Artisan Generators**: Easily generate tabs, sub-tabs, static pages, settings classes, and migrations using `php artisan make:settings-tab`, `make:settings-sub-tab`, and `make:settings-page`.
- 🔍 **Automatic Discovery**: Automatically discovers tabs and sub-tabs from your `app/Filament/Settings/Tabs` directory.
- 🔌 **External Package Registration**: Third-party packages can register custom settings tabs via Service Providers.

---

## Installation

You can install the package via composer (`spatie/laravel-settings` will be automatically installed as a dependency):

```bash
composer require islamv/filament-settings-plugin
```

Publish and run the Spatie Settings & Plugin migrations:

```bash
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider"
php artisan vendor:publish --provider="Islamv\FilamentSettingsPlugin\FilamentSettingsServiceProvider"
php artisan migrate
```

---

## Registration in Panel

Register `FilamentSettingsPlugin` in your Filament Panel Provider (e.g. `AdminPanelProvider.php`):

```php
use Islamv\FilamentSettingsPlugin\FilamentSettingsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentSettingsPlugin::make(),
        ]);
}
```

---

## Configuration

The plugin configuration file `config/filament-settings.php` allows you to customize navigation, locales, file upload disk, and discovery behavior:

```php
return [
    'navigation' => [
        'label' => 'Settings',
        'icon'  => 'heroicon-o-cog-6-tooth',
        'group' => null,
        'sort'  => 100,
    ],

    'locales' => [
        'en' => ['label' => 'English', 'direction' => 'ltr'],
        'ar' => ['label' => 'Arabic',  'direction' => 'rtl'],
    ],

    'uploads' => [
        'disk'      => env('FILAMENT_SETTINGS_DISK', 'public'),
        'directory' => env('FILAMENT_SETTINGS_DIRECTORY', 'settings'),
    ],

    'discovery' => [
        'enabled'   => true,
        'path'      => null, // defaults to app/Filament/Settings/Tabs
        'namespace' => null, // defaults to App\Filament\Settings\Tabs
    ],
];
```

---

## Usage & Tab Management

### Fluent API Customization

In your `PanelProvider`:

```php
FilamentSettingsPlugin::make()
    ->useShield(true) // Enable Filament Shield permission checks
    ->withoutDefaultTabs() // Disable built-in General, Social, Static Pages tabs
    ->removeTab('social-links') // Remove a specific tab
    ->locales([
        'en' => ['label' => 'English', 'direction' => 'ltr'],
        'ar' => ['label' => 'Arabic', 'direction' => 'rtl'],
        'fr' => ['label' => 'French', 'direction' => 'ltr'],
    ])
    ->tab(PaymentSettingsTab::make()) // Register custom tab manually
    ->subTab('static-pages', ContactUsSubTab::make()); // Register sub-tab under existing tab
```

---

## Artisan Scaffolding Generators

### 1. Generate a Main Tab
Creates a main Settings Tab along with a Spatie Settings class and database migration.

```bash
php artisan make:settings-tab Payment --sort=40 --icon=heroicon-o-credit-card
```

### 2. Generate a Sub-Tab
Creates a sub-tab nested under a parent tab.

```bash
php artisan make:settings-sub-tab Stripe --parent=payment --sort=10
```

### 3. Generate a Static Page
Creates a translatable static page sub-tab under the built-in `static-pages` main tab.

```bash
php artisan make:settings-page RefundPolicy --sort=40
```

---

## Automatic Discovery Convention

When automatic discovery is enabled, tabs are automatically loaded based on folder structure:

```
app/Filament/Settings/Tabs/
├── PaymentSettingsTab.php            <-- Main Tab
└── Payment/
    ├── StripeSettingsSubTab.php      <-- Sub-Tab under 'payment'
    └── PaypalSettingsSubTab.php      <-- Sub-Tab under 'payment'
```

---

## Shield & Authorization

When Shield integration is enabled (`->useShield(true)`), permission keys follow this convention:

- **Settings Page**: `page_settings`
- **Main Tab**: `page_settings_{tab_key}` (e.g. `page_settings_general`, `page_settings_payment`)
- **Sub-Tab**: `page_settings_{parent_key}_{subtab_key}` (e.g. `page_settings_static_pages_privacy_policy`)

---

## Third-Party Package Registration

External packages can register settings tabs in their ServiceProvider `boot()` method:

```php
use Islamv\FilamentSettingsPlugin\Registry\SettingsRegistry;

public function boot(): void
{
    $registry = app(SettingsRegistry::class);
    $registry->register(MyPackageSettingsTab::make());
}
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
