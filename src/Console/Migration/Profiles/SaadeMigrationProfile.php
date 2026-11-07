<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration\Profiles;

use EvanSchleret\LaravelUuidable\Console\Migration\AbstractTraitMigrationProfile;

final class SaadeMigrationProfile extends AbstractTraitMigrationProfile
{
    public function name(): string
    {
        return 'saade';
    }

    public function packageName(): string
    {
        return 'saade/laravel-uuid';
    }

    protected function traitMappings(): array
    {
        return [
            'Saade\\Uuid\\Concerns\\HasUuid' => 'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid',
        ];
    }

    public function notes(): array
    {
        return [
            'Review custom uuidColumn() methods and configuration before applying the migration.',
            'The old package defaults to Laravel ordered UUIDs while Laravel UUIDable defaults to UUIDv7.',
        ];
    }
}
