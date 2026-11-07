<?php

use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\DyryndaMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\KblaisMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\KdumaMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\LaravelNativeMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\SaadeMigrationProfile;

return [
    'generation' => 'v7',
    'column' => 'uuid',
    'migration_paths' => ['app', 'config', 'database', 'routes', 'tests'],
    'migration_profiles' => [
        DyryndaMigrationProfile::class,
        SaadeMigrationProfile::class,
        KblaisMigrationProfile::class,
        KdumaMigrationProfile::class,
        LaravelNativeMigrationProfile::class,
    ],
];
