<?php

namespace EvanSchleret\LaravelUuidable\Enums;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

enum UuidGeneration: string
{
    case V1 = 'v1';
    case V4 = 'v4';
    case V6 = 'v6';
    case V7 = 'v7';
    case ORDERED = 'ordered';

    public function generate(): string
    {
        return match ($this) {
            self::V1 => (string) Uuid::uuid1(),
            self::V4 => (string) Str::uuid(),
            self::V6 => (string) Uuid::uuid6(),
            self::V7 => (string) Str::uuid7(),
            self::ORDERED => (string) Str::orderedUuid(),
        };
    }
}
