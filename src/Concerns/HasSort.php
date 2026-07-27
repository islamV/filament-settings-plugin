<?php

declare(strict_types=1);

namespace Islamv\FilamentSettingsPlugin\Concerns;

trait HasSort
{
    protected int $sort = 10;

    public function sort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }
}
