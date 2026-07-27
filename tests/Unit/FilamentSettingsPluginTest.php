<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tests\Unit;

use Islamv\FilamentSettingsPlugin\FilamentSettingsPlugin;
use Islamv\FilamentSettingsPlugin\Registry\SettingsRegistry;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;
use Islamv\FilamentSettingsPlugin\Tests\TestCase;

class FilamentSettingsPluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Fresh registry for each test
        app(SettingsRegistry::class)->flush();
    }

    public function test_plugin_has_correct_id(): void
    {
        $plugin = FilamentSettingsPlugin::make();

        $this->assertSame('filament-settings', $plugin->getId());
    }

    public function test_default_tabs_enabled_by_default(): void
    {
        $plugin = FilamentSettingsPlugin::make();

        $this->assertTrue($this->getProperty($plugin, 'withDefaultTabs'));
    }

    public function test_without_default_tabs_disables_defaults(): void
    {
        $plugin = FilamentSettingsPlugin::make()->withoutDefaultTabs();

        $this->assertFalse($this->getProperty($plugin, 'withDefaultTabs'));
    }

    public function test_shield_disabled_by_default(): void
    {
        $plugin = FilamentSettingsPlugin::make();

        $this->assertFalse($plugin->isShieldEnabled());
    }

    public function test_shield_can_be_enabled(): void
    {
        $plugin = FilamentSettingsPlugin::make()->useShield(true);

        $this->assertTrue($plugin->isShieldEnabled());
    }

    public function test_shield_throws_if_class_not_installed(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/bezhansalleh\/filament-shield/');

        // Only throws when resolveAndRegisterTabs is called.
        // We call it by calling boot()... but boot() needs a panel.
        // Instead, test the guard directly via assertShieldAvailable:
        $plugin = FilamentSettingsPlugin::make()->useShield(true);

        // Call the private method via reflection
        $ref    = new \ReflectionMethod($plugin, 'assertShieldAvailable');
        $ref->setAccessible(true);

        // Only throws if shield class doesn't exist — in test env it won't
        if (! class_exists(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::class)) {
            $ref->invoke($plugin);
        } else {
            $this->markTestSkipped('Shield IS installed, cannot test the exception path.');
        }
    }

    public function test_locales_returns_config_when_not_set(): void
    {
        $plugin = FilamentSettingsPlugin::make();
        config()->set('filament-settings.locales', ['en' => 'English', 'ar' => 'Arabic']);

        $locales = $plugin->getLocales();

        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('ar', $locales);
    }

    public function test_locales_can_be_overridden(): void
    {
        $plugin = FilamentSettingsPlugin::make()->locales([
            'fr' => ['label' => 'French', 'direction' => 'ltr'],
        ]);

        $locales = $plugin->getLocales();

        $this->assertArrayHasKey('fr', $locales);
        $this->assertArrayNotHasKey('en', $locales);
    }

    public function test_can_add_manual_tab(): void
    {
        $plugin = FilamentSettingsPlugin::make()->withoutDefaultTabs();
        $tab    = $this->makeFakeTab('custom', 10);

        $plugin->tab($tab);

        $manualTabs = $this->getProperty($plugin, 'manualTabs');
        $this->assertCount(1, $manualTabs);
    }

    public function test_can_remove_tab_key(): void
    {
        $plugin = FilamentSettingsPlugin::make()->removeTab('general');

        $removedTabs = $this->getProperty($plugin, 'removedTabs');
        $this->assertContains('general', $removedTabs);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    private function getProperty(object $object, string $property): mixed
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setAccessible(true);

        return $ref->getValue($object);
    }

    private function makeFakeTab(string $key, int $sort): SettingsTab
    {
        return new class($key, $sort) extends SettingsTab {
            public function __construct(
                private readonly string $k,
                private readonly int $s,
            ) {
                $this->sort = $s;
            }

            public function getKey(): string
            {
                return $this->k;
            }

            public function getLabel(): string
            {
                return ucfirst($this->k);
            }

            public function schema(): array
            {
                return [];
            }
        };
    }
}
