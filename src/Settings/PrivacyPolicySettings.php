<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Stores multilingual content as an array keyed by locale code.
 *
 * Example: ['ar' => '<p>...</p>', 'en' => '<p>...</p>']
 */
class PrivacyPolicySettings extends Settings
{
    /** @var array<string, string> */
    public array $content = [];

    public static function group(): string
    {
        return 'static_pages_privacy_policy';
    }
}
