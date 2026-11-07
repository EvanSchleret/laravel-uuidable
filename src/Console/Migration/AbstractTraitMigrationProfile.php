<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration;

abstract class AbstractTraitMigrationProfile implements MigrationProfile
{
    abstract protected function traitMappings(): array;

    public function matches(string $contents): bool
    {
        foreach (array_keys($this->traitMappings()) as $trait) {
            if (str_contains(strtolower($contents), strtolower($trait))) {
                return true;
            }

            $separator = strrpos($trait, '\\');
            $namespace = $separator === false ? '' : substr($trait, 0, $separator);
            $shortName = $separator === false ? $trait : substr($trait, $separator + 1);

            if ($namespace !== ''
                && str_contains(strtolower($contents), strtolower($namespace.'\\{'))
                && str_contains(strtolower($contents), strtolower($shortName))) {
                return true;
            }
        }

        return false;
    }

    public function migrate(string $contents, PhpTraitMigrator $migrator): MigrationResult
    {
        return $migrator->migrate($contents, $this->traitMappings());
    }

    public function notes(): array
    {
        return [];
    }
}
