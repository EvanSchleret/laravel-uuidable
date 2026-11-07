<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration;

interface MigrationProfile
{
    public function name(): string;

    public function packageName(): string;

    public function matches(string $contents): bool;

    public function migrate(string $contents, PhpTraitMigrator $migrator): MigrationResult;

    public function notes(): array;
}
