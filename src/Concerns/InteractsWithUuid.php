<?php

namespace EvanSchleret\LaravelUuidable\Concerns;

use EvanSchleret\LaravelUuidable\Enums\UuidGeneration;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithUuid
{
    abstract protected function uuidColumn(): string;

    protected function uuidGeneration(): UuidGeneration
    {
        return UuidGeneration::from((string) config('uuidable.generation', UuidGeneration::V7->value));
    }

    public function scopeWhereUuid(Builder $query, string|array $uuids): Builder
    {
        return $query->whereIn(
            $this->qualifyColumn($this->uuidColumn()),
            is_array($uuids) ? array_values($uuids) : [$uuids]
        );
    }

    public function scopeWhereNotUuid(Builder $query, string|array $uuids): Builder
    {
        return $query->whereNotIn(
            $this->qualifyColumn($this->uuidColumn()),
            is_array($uuids) ? array_values($uuids) : [$uuids]
        );
    }
}
