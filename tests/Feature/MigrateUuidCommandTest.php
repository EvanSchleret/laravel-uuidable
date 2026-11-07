<?php

use EvanSchleret\LaravelUuidable\Console\Migration\PhpTraitMigrator;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\DyryndaMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\KblaisMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\KdumaMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\LaravelNativeMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\Profiles\SaadeMigrationProfile;

function createLaravelUuidableMigrationFixture(string $contents): array
{
    $relativeDirectory = 'uuidable-migration-fixtures/'.bin2hex(random_bytes(8));
    $absoluteDirectory = base_path($relativeDirectory);
    $relativeFile = $relativeDirectory.'/Example.php';

    mkdir($absoluteDirectory, 0777, true);
    file_put_contents(base_path($relativeFile), $contents);

    return [$absoluteDirectory, $relativeFile, base_path($relativeFile)];
}

function removeLaravelUuidableMigrationFixture(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($directory);
}

it('rewrites trait imports for every built-in migration profile', function (
    string $profileClass,
    string $sourceTrait,
    string $sourceShortName,
    string $targetTrait,
    string $targetShortName
): void {
    $contents = "<?php\n\nnamespace App\\Models;\n\nuse {$sourceTrait};\n\nclass Example\n{\n    use {$sourceShortName};\n}\n";
    $profile = new $profileClass;
    $result = $profile->migrate($contents, new PhpTraitMigrator);

    expect($result->supported)->toBeTrue()
        ->and($result->contents)->toContain("use {$targetTrait};")
        ->and($result->contents)->toContain("    use {$targetShortName};")
        ->and($result->contents)->not->toContain($sourceTrait);
})->with([
    'dyrynda' => [
        DyryndaMigrationProfile::class,
        'Dyrynda\\Database\\Support\\GeneratesUuid',
        'GeneratesUuid',
        'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid',
        'HasUuid',
    ],
    'saade' => [
        SaadeMigrationProfile::class,
        'Saade\\Uuid\\Concerns\\HasUuid',
        'HasUuid',
        'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid',
        'HasUuid',
    ],
    'kblais' => [
        KblaisMigrationProfile::class,
        'Kblais\\Uuid\\Uuid',
        'Uuid',
        'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuidPrimaryKey',
        'HasUuidPrimaryKey',
    ],
    'kduma' => [
        KdumaMigrationProfile::class,
        'KDuma\\Eloquent\\Uuidable',
        'Uuidable',
        'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid',
        'HasUuid',
    ],
    'laravel' => [
        LaravelNativeMigrationProfile::class,
        'Illuminate\\Database\\Eloquent\\Concerns\\HasUuids',
        'HasUuids',
        'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuidPrimaryKey',
        'HasUuidPrimaryKey',
    ],
]);

it('converts the KDuma field attribute to a model column method', function (): void {
    $contents = <<<'PHP'
<?php

namespace App\Models;

use KDuma\Eloquent\Uuidable;
use KDuma\Eloquent\Attributes\HasUuid;

#[HasUuid(field: 'public_id')]
class Example extends Model
{
    use Uuidable;
}
PHP;
    $profile = new KdumaMigrationProfile;
    $result = $profile->migrate($contents, new PhpTraitMigrator);

    expect($result->supported)->toBeTrue()
        ->and($result->contents)->toContain('use EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid;')
        ->and($result->contents)->toContain('protected function uuidColumn(): string')
        ->and($result->contents)->toContain("return 'public_id';")
        ->and($result->contents)->not->toContain('KDuma\\Eloquent');
});

it('previews migration changes without writing them', function (): void {
    $contents = <<<'PHP'
<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;

class Example
{
    use GeneratesUuid;
}
PHP;
    [$directory, $relativeFile, $absoluteFile] = createLaravelUuidableMigrationFixture($contents);

    try {
        $this->artisan('uuidable:migrate', [
            '--from' => ['dyrynda'],
            '--path' => [$relativeFile],
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Dry run complete; no files were changed.')
            ->assertExitCode(0);

        expect(file_get_contents($absoluteFile))->toBe($contents);
    } finally {
        removeLaravelUuidableMigrationFixture($directory);
    }
});

it('applies migration changes when forced', function (): void {
    $contents = <<<'PHP'
<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;

class Example
{
    use GeneratesUuid;
}
PHP;
    [$directory, $relativeFile, $absoluteFile] = createLaravelUuidableMigrationFixture($contents);

    try {
        $this->artisan('uuidable:migrate', [
            '--from' => ['dyrynda'],
            '--path' => [$relativeFile],
            '--force' => true,
        ])
            ->expectsOutputToContain('UUID package migration completed.')
            ->assertExitCode(0);

        $migrated = (string) file_get_contents($absoluteFile);

        expect($migrated)
            ->toContain('use EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid;')
            ->toContain('    use HasUuid;')
            ->not->toContain('Dyrynda\\Database');
    } finally {
        removeLaravelUuidableMigrationFixture($directory);
    }
});

it('does not change files when a grouped trait import needs manual conversion', function (): void {
    $contents = <<<'PHP'
<?php

namespace App\Models;

use Dyrynda\Database\Support\{GeneratesUuid};

class Example
{
    use GeneratesUuid;
}
PHP;
    [$directory, $relativeFile, $absoluteFile] = createLaravelUuidableMigrationFixture($contents);

    try {
        $this->artisan('uuidable:migrate', [
            '--from' => ['dyrynda'],
            '--path' => [$relativeFile],
            '--force' => true,
        ])
            ->expectsOutputToContain('Grouped use imports are not rewritten automatically.')
            ->assertExitCode(1);

        expect(file_get_contents($absoluteFile))->toBe($contents);
    } finally {
        removeLaravelUuidableMigrationFixture($directory);
    }
});

it('prompts for profiles when the from option is omitted', function (): void {
    $contents = <<<'PHP'
<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;

class Example
{
    use GeneratesUuid;
}
PHP;
    [$directory, $relativeFile, $absoluteFile] = createLaravelUuidableMigrationFixture($contents);

    try {
        $this->artisan('uuidable:migrate', [
            '--path' => [$relativeFile],
            '--dry-run' => true,
        ])
            ->expectsChoice(
                'Which UUID integration should be migrated?',
                ['dyrynda'],
                ['dyrynda', 'saade', 'kblais', 'kduma', 'laravel'],
                null,
                true
            )
            ->expectsOutputToContain('Dry run complete; no files were changed.')
            ->assertExitCode(0);

        expect(file_get_contents($absoluteFile))->toBe($contents);
    } finally {
        removeLaravelUuidableMigrationFixture($directory);
    }
});
