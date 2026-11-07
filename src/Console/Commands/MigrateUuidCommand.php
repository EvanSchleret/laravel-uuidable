<?php

namespace EvanSchleret\LaravelUuidable\Console\Commands;

use EvanSchleret\LaravelUuidable\Console\Migration\MigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\MigrationProfileRegistry;
use EvanSchleret\LaravelUuidable\Console\Migration\PhpTraitMigrator;
use FilesystemIterator;
use Illuminate\Console\Command;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class MigrateUuidCommand extends Command
{
    protected $signature = 'uuidable:migrate
        {--from=* : Source profile name or Composer package name}
        {--path=* : Project-relative directory or PHP file to scan}
        {--dry-run : Preview changes without writing files}
        {--force : Apply changes without a confirmation prompt}';

    protected $description = 'Migrate Eloquent UUID traits to Laravel UUIDable';

    public function handle(MigrationProfileRegistry $registry, PhpTraitMigrator $migrator): int
    {
        $profiles = $this->selectedProfiles($registry);

        if ($profiles === null) {
            return self::FAILURE;
        }

        $files = $this->filesToScan();

        if ($files === null) {
            return self::FAILURE;
        }

        $plans = [];
        $warnings = [];
        $unsupportedFiles = [];

        foreach ($files as $file) {
            $original = file_get_contents($file);

            if ($original === false) {
                $warnings[] = $this->relativePath($file).': unable to read the file.';

                continue;
            }

            $contents = $original;
            $usedProfiles = [];
            $notes = [];
            $fileWarnings = [];
            $supported = true;

            foreach ($profiles as $profile) {
                if (! $profile->matches($contents)) {
                    continue;
                }

                $result = $profile->migrate($contents, $migrator);
                $fileWarnings = array_merge($fileWarnings, $result->warnings);

                if (! $result->supported) {
                    $supported = false;

                    break;
                }

                if ($result->changedFrom($contents)) {
                    $contents = $result->contents;
                    $usedProfiles[] = $profile->name();
                    $notes = array_merge($notes, $profile->notes());
                }
            }

            foreach ($fileWarnings as $warning) {
                $warnings[] = $this->relativePath($file).': '.$warning;
            }

            if (! $supported) {
                $unsupportedFiles[] = $file;

                continue;
            }

            if ($contents !== $original) {
                $plans[$file] = [
                    'contents' => $contents,
                    'profiles' => array_values(array_unique($usedProfiles)),
                    'notes' => array_values(array_unique($notes)),
                ];
            }
        }

        if ($plans === [] && $warnings === []) {
            $this->info('No matching UUID package traits were found.');

            return self::SUCCESS;
        }

        if ($plans !== []) {
            $this->newLine();
            $this->info($this->option('dry-run') ? 'Files that would be updated:' : 'Files to update:');

            foreach ($plans as $file => $plan) {
                $this->line('  '.$this->relativePath($file).' ('.implode(', ', $plan['profiles']).')');
            }
        }

        foreach (array_values(array_unique($warnings)) as $warning) {
            $this->warn($warning);
        }

        $manualNotes = [];

        foreach ($plans as $plan) {
            $manualNotes = array_merge($manualNotes, $plan['notes']);
        }

        foreach (array_values(array_unique($manualNotes)) as $note) {
            $this->comment('Review: '.$note);
        }

        if ($unsupportedFiles !== []) {
            $this->error('No files were changed because at least one matching file needs a manual conversion first.');

            return self::FAILURE;
        }

        if ($plans === []) {
            $this->error('No supported automatic changes could be prepared.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run complete; no files were changed.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                $this->error('Use --dry-run to preview or --force to apply changes non-interactively.');

                return self::FAILURE;
            }

            if (! $this->confirm('Apply these changes to your application files?')) {
                $this->comment('Migration cancelled; no files were changed.');

                return self::SUCCESS;
            }
        }

        foreach ($plans as $file => $plan) {
            if (file_put_contents($file, $plan['contents'], LOCK_EX) === false) {
                $this->error('Unable to write '.$this->relativePath($file).'.');

                return self::FAILURE;
            }
        }

        $this->info('UUID package migration completed.');

        return self::SUCCESS;
    }

    private function selectedProfiles(MigrationProfileRegistry $registry): ?array
    {
        $availableProfiles = $registry->all();

        if ($availableProfiles === []) {
            $this->error('No UUID migration profiles are configured.');

            return null;
        }

        $requested = $this->option('from') ?: [];

        if ($requested === []) {
            if (! $this->input->isInteractive()) {
                $this->error('Choose a source profile with --from, or run the command interactively.');

                return null;
            }

            $suggested = $this->profilesInComposerLock($registry);
            $profiles = $registry->all();

            usort($profiles, static function (MigrationProfile $first, MigrationProfile $second) use ($suggested): int {
                return (int) in_array($second->name(), $suggested, true) <=> (int) in_array($first->name(), $suggested, true);
            });

            $choices = array_map(
                static fn (MigrationProfile $profile): string => $profile->name(),
                $profiles
            );

            $this->line($suggested === []
                ? 'Supported source profiles: '.implode(', ', $choices)
                : 'Suggested from composer.lock: '.implode(', ', $suggested).'. Available: '.implode(', ', $choices));

            $selected = $this->choice(
                'Which UUID integration should be migrated?',
                $choices,
                null,
                null,
                true
            );

            $requested = is_array($selected) ? $selected : [$selected];
        }

        $selectedProfiles = [];

        foreach ($requested as $name) {
            $profile = $registry->find((string) $name);

            if ($profile === null) {
                $available = array_map(static fn (MigrationProfile $item): string => $item->name(), $registry->all());
                $this->error('Unknown migration profile "'.$name.'". Available profiles: '.implode(', ', $available).'.');

                return null;
            }

            $selectedProfiles[$profile->name()] = $profile;
        }

        return array_values($selectedProfiles);
    }

    private function profilesInComposerLock(MigrationProfileRegistry $registry): array
    {
        $lockPath = base_path('composer.lock');

        if (! is_file($lockPath)) {
            return [];
        }

        $lock = json_decode((string) file_get_contents($lockPath), true);

        if (! is_array($lock)) {
            return [];
        }

        $installed = [];

        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            if (is_array($package) && isset($package['name'])) {
                $installed[] = (string) $package['name'];
            }
        }

        $suggested = [];

        foreach ($registry->all() as $profile) {
            if ($profile->name() !== 'laravel' && in_array($profile->packageName(), $installed, true)) {
                $suggested[] = $profile->name();
            }
        }

        return $suggested;
    }

    private function filesToScan(): ?array
    {
        $base = realpath(base_path());

        if ($base === false) {
            $this->error('The Laravel application base path could not be resolved.');

            return null;
        }

        $paths = $this->option('path') ?: config('uuidable.migration_paths', ['app', 'config', 'database', 'routes', 'tests']);
        $files = [];

        foreach ($paths as $path) {
            if (str_starts_with((string) $path, DIRECTORY_SEPARATOR)) {
                $this->error('Scan paths must be relative to the Laravel application root.');

                return null;
            }

            $absolutePath = realpath($base.DIRECTORY_SEPARATOR.(string) $path);

            if ($absolutePath === false) {
                continue;
            }

            if ($absolutePath !== $base && ! str_starts_with($absolutePath, $base.DIRECTORY_SEPARATOR)) {
                $this->error('Scan paths must stay inside the Laravel application root.');

                return null;
            }

            if (is_file($absolutePath)) {
                if (pathinfo($absolutePath, PATHINFO_EXTENSION) === 'php') {
                    $files[$absolutePath] = $absolutePath;
                }

                continue;
            }

            foreach ($this->phpFiles($absolutePath) as $file) {
                $files[$file] = $file;
            }
        }

        return array_values($files);
    }

    private function phpFiles(string $directory): iterable
    {
        $excludedDirectories = ['.git', 'bootstrap', 'node_modules', 'storage', 'vendor'];
        $iterator = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $current) use ($excludedDirectories): bool {
                if ($current->isLink()) {
                    return false;
                }

                return ! $current->isDir() || ! in_array($current->getFilename(), $excludedDirectories, true);
            }
        );

        foreach (new RecursiveIteratorIterator($iterator) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
