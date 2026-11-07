<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration\Profiles;

use EvanSchleret\LaravelUuidable\Console\Migration\AbstractTraitMigrationProfile;

final class KblaisMigrationProfile extends AbstractTraitMigrationProfile
{
    public function name(): string
    {
        return 'kblais';
    }

    public function packageName(): string
    {
        return 'kblais/laravel-uuid';
    }

    protected function traitMappings(): array
    {
        return [
            'Kblais\\Uuid\\Uuid' => 'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuidPrimaryKey',
        ];
    }

    public function notes(): array
    {
        return [
            'UUIDv3 and UUIDv5 custom generation are not supported by Laravel UUIDable; review uuidVersion and uuidString overrides.',
            'The old package defaults to UUIDv4 while Laravel UUIDable defaults to UUIDv7.',
        ];
    }
}
