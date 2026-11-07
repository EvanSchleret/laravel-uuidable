<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration;

use InvalidArgumentException;

final class MigrationProfileRegistry
{
    private array $profiles = [];

    public function __construct(iterable $profiles)
    {
        foreach ($profiles as $profile) {
            if (! $profile instanceof MigrationProfile) {
                throw new InvalidArgumentException('Migration profiles must implement '.MigrationProfile::class.'.');
            }

            $this->profiles[$profile->name()] = $profile;
        }
    }

    public function all(): array
    {
        return array_values($this->profiles);
    }

    public function find(string $name): ?MigrationProfile
    {
        foreach ($this->profiles as $profile) {
            if (strcasecmp($profile->name(), $name) === 0 || strcasecmp($profile->packageName(), $name) === 0) {
                return $profile;
            }
        }

        return null;
    }
}
