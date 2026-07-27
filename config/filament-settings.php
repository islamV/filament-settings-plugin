<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Configure how the Settings page appears in the Filament sidebar.
    |
    */

    'navigation' => [
        'label' => 'Settings',
        'icon'  => 'heroicon-o-cog-6-tooth',
        'group' => null,
        'sort'  => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Configure the locales available for translatable settings tabs.
    |
    | Simple format:
    |   'ar' => 'Arabic'
    |
    | Rich format with RTL direction support:
    |   'ar' => ['label' => 'Arabic', 'direction' => 'rtl']
    |
    */

    'locales' => [
        'en' => [
            'label'     => 'English',
            'direction' => 'ltr',
        ],
        'ar' => [
            'label'     => 'Arabic',
            'direction' => 'rtl',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Uploads
    |--------------------------------------------------------------------------
    |
    | Configure file upload storage for settings assets (logo, favicon, etc.)
    |
    */

    'uploads' => [
        'disk'      => env('FILAMENT_SETTINGS_DISK', 'public'),
        'directory' => env('FILAMENT_SETTINGS_DIRECTORY', 'settings'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery
    |--------------------------------------------------------------------------
    |
    | Automatic tab and sub-tab discovery from the application directory.
    | Discovered tabs must extend SettingsTab or SettingsSubTab.
    |
    | Discovery paths follow the convention:
    |   Main tabs:  app/Filament/Settings/Tabs/*.php
    |   Sub-tabs:   app/Filament/Settings/Tabs/{ParentName}/*.php
    |
    */

    'discovery' => [
        'enabled'   => true,
        'path'      => null, // null = app/Filament/Settings/Tabs
        'namespace' => null, // null = App\Filament\Settings\Tabs
    ],

];
