<?php

namespace Tests\Models;

use EvanSchleret\LaravelUuidable\Enums\UuidGeneration;

class UuidV4Post extends Post
{
    protected function uuidGeneration(): UuidGeneration
    {
        return UuidGeneration::V4;
    }
}
