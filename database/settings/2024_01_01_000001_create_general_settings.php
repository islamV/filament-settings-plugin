<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.app_name', '');
        $this->migrator->add('general.app_description', '');
        $this->migrator->add('general.logo', null);
        $this->migrator->add('general.favicon', null);
        $this->migrator->add('general.support_email', '');
        $this->migrator->add('general.support_phone', '');
    }

    public function down(): void
    {
        $this->migrator->delete('general.app_name');
        $this->migrator->delete('general.app_description');
        $this->migrator->delete('general.logo');
        $this->migrator->delete('general.favicon');
        $this->migrator->delete('general.support_email');
        $this->migrator->delete('general.support_phone');
    }
};
