<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration\Profiles;

use EvanSchleret\LaravelUuidable\Console\Migration\AbstractTraitMigrationProfile;

final class DyryndaMigrationProfile extends AbstractTraitMigrationProfile
{
    public function name(): string
    {
        return 'dyrynda';
    }

    public function packageName(): string
    {
        return 'dyrynda/laravel-model-uuid';
    }

    protected function traitMappings(): array
    {
        return [
            'Dyrynda\\Database\\Support\\GeneratesUuid' => 'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid',
        ];
    }

    public function notes(): array
    {
        return [
            'Review uuidVersion(), uuidColumns(), model-uuid configuration, and EfficientUuid casts; those settings are not converted.',
            'The old package defaults to UUIDv4 while Laravel UUIDable defaults to UUIDv7. Set uuidGeneration() or uuidable.generation if you need to preserve UUIDv4.',
        ];
    }
}
