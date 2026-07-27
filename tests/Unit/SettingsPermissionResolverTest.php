<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tests\Unit;

use Islamv\FilamentSettingsPlugin\Authorization\SettingsPermissionResolver;
use Islamv\FilamentSettingsPlugin\Tests\TestCase;

class SettingsPermissionResolverTest extends TestCase
{
    private SettingsPermissionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new SettingsPermissionResolver;
    }

    public function test_settings_page_permission_key_format(): void
    {
        $this->assertSame('page_settings', $this->resolver->getSettingsPagePermissionKey());
    }

    public function test_tab_permission_key_format(): void
    {
        $this->assertSame('page_settings_general', $this->resolver->getTabPermissionKey('general'));
        $this->assertSame('page_settings_social_links', $this->resolver->getTabPermissionKey('social-links'));
        $this->assertSame('page_settings_static_pages', $this->resolver->getTabPermissionKey('static-pages'));
    }

    public function test_sub_tab_permission_key_format(): void
    {
        $this->assertSame(
            'page_settings_static_pages_privacy_policy',
            $this->resolver->getSubTabPermissionKey('static-pages', 'privacy-policy')
        );
        $this->assertSame(
            'page_settings_payment_stripe',
            $this->resolver->getSubTabPermissionKey('payment', 'stripe')
        );
    }

    public function test_all_permission_keys_includes_page_key(): void
    {
        $keys = $this->resolver->getAllPermissionKeys(['general', 'social-links']);

        $this->assertArrayHasKey('page_settings', $keys);
        $this->assertArrayHasKey('page_settings_general', $keys);
        $this->assertArrayHasKey('page_settings_social_links', $keys);
    }

    public function test_all_permission_keys_includes_sub_tab_keys(): void
    {
        $keys = $this->resolver->getAllPermissionKeys(
            ['static-pages'],
            ['static-pages' => ['privacy-policy', 'terms', 'about']]
        );

        $this->assertArrayHasKey('page_settings_static_pages_privacy_policy', $keys);
        $this->assertArrayHasKey('page_settings_static_pages_terms', $keys);
        $this->assertArrayHasKey('page_settings_static_pages_about', $keys);
    }

    public function test_can_access_settings_page_without_shield_returns_true(): void
    {
        // When Shield is NOT enabled, canAccess returns true
        // We cannot easily bind a plugin mock, so we test via config
        config()->set('filament-settings', [
            'navigation' => ['label' => 'Settings', 'icon' => null, 'group' => null, 'sort' => 100],
            'locales'    => [],
            'uploads'    => ['disk' => 'public', 'directory' => 'settings'],
            'discovery'  => ['enabled' => false, 'path' => null, 'namespace' => null],
        ]);

        // The resolver will catch the exception from FilamentSettingsPlugin::get() and return true
        $result = $this->resolver->canAccessSettingsPage();

        $this->assertTrue($result);
    }
}
