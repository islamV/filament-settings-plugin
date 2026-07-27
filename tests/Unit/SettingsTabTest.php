<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tests\Unit;

use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;
use Islamv\FilamentSettingsPlugin\Tests\TestCase;

class SettingsTabTest extends TestCase
{
    public function test_tab_visible_by_default(): void
    {
        $tab = $this->makeFakeTab('payments');

        $this->assertTrue($tab->isVisible());
        $this->assertTrue($tab->isAuthorized());
        $this->assertTrue($tab->isAccessible());
    }

    public function test_tab_can_be_hidden(): void
    {
        $tab = $this->makeFakeTab('payments')->visible(false);

        $this->assertFalse($tab->isVisible());
        $this->assertFalse($tab->isAccessible());
    }

    public function test_tab_visible_can_accept_closure(): void
    {
        $tab = $this->makeFakeTab('payments')->visible(fn () => false);

        $this->assertFalse($tab->isVisible());
    }

    public function test_tab_authorize_closure_returning_false_blocks(): void
    {
        $tab = $this->makeFakeTab('payments')->authorize(fn () => false);

        $this->assertFalse($tab->isAuthorized());
        $this->assertFalse($tab->isAccessible());
    }

    public function test_tab_sort_default_and_override(): void
    {
        $tab = $this->makeFakeTab('payments');
        $this->assertSame(10, $tab->getSort());

        $tab->sort(99);
        $this->assertSame(99, $tab->getSort());
    }

    public function test_tab_permission_key_auto_derived(): void
    {
        $tab = $this->makeFakeTab('social-links');

        $this->assertSame('page_settings_social_links', $tab->getPermissionKey());
    }

    public function test_tab_permission_key_can_be_overridden(): void
    {
        $tab = $this->makeFakeTab('payments')->permission('custom_payment_permission');

        $this->assertSame('custom_payment_permission', $tab->getPermissionKey());
    }

    public function test_tab_sub_tab_addition_prevents_duplicates(): void
    {
        $tab = $this->makeFakeTab('static-pages');
        $sub = $this->makeFakeSubTab('privacy-policy');

        $tab->addSubTab($sub);
        $tab->addSubTab($sub); // Should be deduplicated

        $this->assertCount(1, $tab->getSubTabs());
    }

    public function test_tab_sub_tabs_sorted_by_sort(): void
    {
        $tab  = $this->makeFakeTab('parent');
        $sub1 = $this->makeFakeSubTab('c')->sort(30);
        $sub2 = $this->makeFakeSubTab('a')->sort(10);
        $sub3 = $this->makeFakeSubTab('b')->sort(20);

        $tab->addSubTab($sub1);
        $tab->addSubTab($sub2);
        $tab->addSubTab($sub3);

        $sorted = $tab->getSubTabs();
        $this->assertSame(['a', 'b', 'c'], array_map(fn ($s) => $s->getKey(), $sorted));
    }

    public function test_before_save_callback_transforms_data(): void
    {
        $tab = $this->makeFakeTab('general')->beforeSave(function (array $data): array {
            $data['app_name'] = strtoupper($data['app_name']);

            return $data;
        });

        $transformed = $tab->callBeforeSave(['app_name' => 'myapp']);

        $this->assertSame('MYAPP', $transformed['app_name']);
    }

    public function test_sub_tab_is_translatable(): void
    {
        $subTab = $this->makeFakeSubTab('privacy-policy')->translatable(true);

        $this->assertTrue($subTab->isTranslatable());
    }

    public function test_sub_tab_parent_key_can_be_set(): void
    {
        $subTab = $this->makeFakeSubTab('privacy-policy');
        $subTab->setParentTabKey('static-pages');

        $this->assertSame('static-pages', $subTab->getParentTabKey());
    }

    public function test_locales_returned_from_config_when_not_set(): void
    {
        config()->set('filament-settings.locales', [
            'en' => ['label' => 'English', 'direction' => 'ltr'],
            'ar' => ['label' => 'Arabic',  'direction' => 'rtl'],
        ]);

        $tab = $this->makeFakeTab('general');

        // Falls back to config since plugin is not registered
        $locales = $tab->getLocales();
        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('ar', $locales);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    private function makeFakeTab(string $key): SettingsTab
    {
        return new class($key) extends SettingsTab {
            public function __construct(private readonly string $k)
            {
                $this->sort = 10;
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

    private function makeFakeSubTab(string $key): SettingsSubTab
    {
        return new class($key) extends SettingsSubTab {
            public function __construct(private readonly string $k)
            {
                $this->sort = 10;
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
