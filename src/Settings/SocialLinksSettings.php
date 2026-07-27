<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Settings;

use Spatie\LaravelSettings\Settings;

class SocialLinksSettings extends Settings
{
    /** @var array */
    public array $links = [];

    public static function group(): string
    {
        return 'social_links';
    }
}
