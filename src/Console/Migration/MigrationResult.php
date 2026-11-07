<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration;

final readonly class MigrationResult
{
    public function __construct(
        public string $contents,
        public array $warnings = [],
        public bool $supported = true,
    ) {}

    public function changedFrom(string $original): bool
    {
        return $this->contents !== $original;
    }
}
