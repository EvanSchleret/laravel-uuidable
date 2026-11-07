<?php

namespace EvanSchleret\LaravelUuidable\Concerns;

trait HasUuid
{
    use InteractsWithUuid;

    public static function bootHasUuid(): void
    {
        static::creating(static function (self $model): void {
            $column = $model->uuidColumn();
            $value = $model->getAttribute($column);

            if ($value !== null && $value !== '') {
                return;
            }

            $model->setAttribute($column, $model->uuidGeneration()->generate());
        });
    }

    protected function uuidColumn(): string
    {
        return (string) config('uuidable.column', 'uuid');
    }
}
