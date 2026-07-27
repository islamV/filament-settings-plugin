<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Settings;

use Spatie\LaravelSettings\Settings;

class SocialLinksSettings extends Settings
{
    /**
     * Array of social link entries:
     * [['name' => 'facebook', 'url' => 'https://...'], ...]
     *
     * @var array<int, array{name: string, url: string}>
     */
    public array $links = [];

    public static function group(): string
    {
        return 'social_links';
    }
}
