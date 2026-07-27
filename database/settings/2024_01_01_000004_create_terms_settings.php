<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('static_pages_terms.content', []);
    }

    public function down(): void
    {
        $this->migrator->delete('static_pages_terms.content');
    }
};
