<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Tests\Unit;

use Islamv\FilamentSettingsPlugin\Registry\SettingsRegistry;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsTab;
use Islamv\FilamentSettingsPlugin\Tabs\SettingsSubTab;
use Islamv\FilamentSettingsPlugin\Tests\TestCase;

class SettingsRegistryTest extends TestCase
{
    private SettingsRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new SettingsRegistry;
    }

    public function test_can_register_a_tab(): void
    {
        $tab = $this->makeFakeTab('payments', 10);

        $this->registry->registerTab($tab);

        $this->assertTrue($this->registry->hasTab('payments'));
    }

    public function test_first_registered_wins_without_force(): void
    {
        $original    = $this->makeFakeTab('payments', 10);
        $replacement = $this->makeFakeTab('payments', 99);

        $this->registry->registerTab($original);
        $this->registry->registerTab($replacement); // should NOT replace

        $resolved = $this->registry->getResolvedTabs();

        $this->assertCount(1, $resolved);
        $this->assertSame(10, $resolved[0]->getSort()); // original sort
    }

    public function test_can_force_replace_tab(): void
    {
        $original    = $this->makeFakeTab('payments', 10);
        $replacement = $this->makeFakeTab('payments', 99);

        $this->registry->registerTab($original);
        $this->registry->registerTab($replacement, force: true);

        $resolved = $this->registry->getResolvedTabs();

        $this->assertCount(1, $resolved);
        $this->assertSame(99, $resolved[0]->getSort());
    }

    public function test_can_remove_tab(): void
    {
        $tab = $this->makeFakeTab('payments', 10);

        $this->registry->registerTab($tab);
        $this->registry->removeTab('payments');

        $this->assertFalse($this->registry->hasTab('payments'));
    }

    public function test_tabs_sorted_by_sort_value(): void
    {
        $this->registry->registerTab($this->makeFakeTab('c', 30));
        $this->registry->registerTab($this->makeFakeTab('a', 10));
        $this->registry->registerTab($this->makeFakeTab('b', 20));

        $resolved = $this->registry->getResolvedTabs();

        $this->assertSame(['a', 'b', 'c'], array_map(fn ($t) => $t->getKey(), $resolved));
    }

    public function test_sub_tabs_attached_to_parent(): void
    {
        $parentTab = $this->makeFakeTab('static-pages', 10);
        $subTab    = $this->makeFakeSubTab('privacy-policy', 'static-pages');

        $this->registry->registerTab($parentTab);
        $this->registry->registerSubTab($subTab);

        $resolved = $this->registry->getResolvedTabs();

        $this->assertCount(1, $resolved);
        $this->assertTrue($resolved[0]->hasSubTabs());
        $this->assertCount(1, $resolved[0]->getSubTabs());
    }

    public function test_orphaned_sub_tab_not_attached(): void
    {
        // Sub-tab with no matching parent
        $subTab = $this->makeFakeSubTab('payment-sub', 'non-existent-parent');

        $this->registry->registerSubTab($subTab);
        $resolved = $this->registry->getResolvedTabs();

        $this->assertCount(0, $resolved);
    }

    public function test_registry_flush_clears_all(): void
    {
        $this->registry->registerTab($this->makeFakeTab('payments', 10));
        $this->registry->flush();

        $this->assertFalse($this->registry->hasTab('payments'));
        $this->assertCount(0, $this->registry->getResolvedTabs());
    }

    public function test_duplicate_sub_tabs_not_registered(): void
    {
        $parent = $this->makeFakeTab('static-pages', 10);
        $sub1   = $this->makeFakeSubTab('privacy-policy', 'static-pages');
        $sub2   = $this->makeFakeSubTab('privacy-policy', 'static-pages'); // duplicate key

        $this->registry->registerTab($parent);
        $this->registry->registerSubTab($sub1);
        $this->registry->registerSubTab($sub2); // should be ignored

        $resolved = $this->registry->getResolvedTabs();
        $this->assertCount(1, $resolved[0]->getSubTabs());
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

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

    private function makeFakeSubTab(string $key, string $parentKey): SettingsSubTab
    {
        return new class($key, $parentKey) extends SettingsSubTab {
            public function __construct(
                private readonly string $k,
                private readonly string $p,
            ) {
                $this->parentTabKey = $p;
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
