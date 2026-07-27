<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $app_name = '';

    public string $app_description = '';

    public ?string $logo = null;

    public ?string $favicon = null;

    public string $support_email = '';

    public string $support_phone = '';

    public static function group(): string
    {
        return 'general';
    }
}
