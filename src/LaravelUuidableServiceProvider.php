<?php

namespace EvanSchleret\LaravelUuidable;

use EvanSchleret\LaravelUuidable\Console\Commands\MigrateUuidCommand;
use EvanSchleret\LaravelUuidable\Console\Migration\MigrationProfileRegistry;
use Illuminate\Support\ServiceProvider;

class LaravelUuidableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/uuidable.php', 'uuidable');

        $this->app->singleton(MigrationProfileRegistry::class, function ($app): MigrationProfileRegistry {
            $profiles = array_map(
                static fn (string $profile): object => $app->make($profile),
                config('uuidable.migration_profiles', [])
            );

            return new MigrationProfileRegistry($profiles);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([MigrateUuidCommand::class]);
        }

        $this->publishes([
            __DIR__.'/../config/uuidable.php' => config_path('uuidable.php'),
        ], 'uuidable-config');
    }
}
