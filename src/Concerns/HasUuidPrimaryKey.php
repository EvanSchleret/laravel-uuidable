<?php

namespace EvanSchleret\LaravelUuidable\Concerns;

trait HasUuidPrimaryKey
{
    use InteractsWithUuid;

    public static function bootHasUuidPrimaryKey(): void
    {
        static::creating(static function (self $model): void {
            $key = $model->getKeyName();
            $value = $model->getAttribute($key);

            if ($value !== null && $value !== '') {
                return;
            }

            $model->setAttribute($key, $model->uuidGeneration()->generate());
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    protected function uuidColumn(): string
    {
        return $this->getKeyName();
    }
}
