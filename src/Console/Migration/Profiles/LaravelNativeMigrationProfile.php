<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration\Profiles;

use EvanSchleret\LaravelUuidable\Console\Migration\AbstractTraitMigrationProfile;

final class LaravelNativeMigrationProfile extends AbstractTraitMigrationProfile
{
    public function name(): string
    {
        return 'laravel';
    }

    public function packageName(): string
    {
        return 'laravel/framework';
    }

    protected function traitMappings(): array
    {
        return [
            'Illuminate\\Database\\Eloquent\\Concerns\\HasUuids' => 'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuidPrimaryKey',
        ];
    }

    public function notes(): array
    {
        return [
            'Review custom uniqueIds() and newUniqueId() methods; they are not converted.',
            'Laravel HasUuids generates ordered UUIDs by default while Laravel UUIDable defaults to UUIDv7.',
        ];
    }
}
