<?php

namespace EvanSchleret\LaravelUuidable\Concerns;

trait BindsToUuid
{
    abstract protected function uuidColumn(): string;

    public function getRouteKeyName(): string
    {
        return $this->uuidColumn();
    }
}
