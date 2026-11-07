<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration\Profiles;

use EvanSchleret\LaravelUuidable\Console\Migration\AbstractTraitMigrationProfile;
use EvanSchleret\LaravelUuidable\Console\Migration\MigrationResult;
use EvanSchleret\LaravelUuidable\Console\Migration\PhpTraitMigrator;

final class KdumaMigrationProfile extends AbstractTraitMigrationProfile
{
    public function name(): string
    {
        return 'kduma';
    }

    public function packageName(): string
    {
        return 'kduma/eloquent-uuidable';
    }

    protected function traitMappings(): array
    {
        return [
            'KDuma\\Eloquent\\Uuidable' => 'EvanSchleret\\LaravelUuidable\\Concerns\\HasUuid',
        ];
    }

    public function migrate(string $contents, PhpTraitMigrator $migrator): MigrationResult
    {
        $attributeClass = 'KDuma\\Eloquent\\Attributes\\HasUuid';
        $importPattern = '~(?m)^[ \t]*use[ \t]+\\\\?'.preg_quote($attributeClass, '~').'(?<alias>[ \t]+as[ \t]+[A-Za-z_][A-Za-z0-9_]*)?[ \t]*;[ \t]*\R?~';
        $hasAttributeImport = preg_match($importPattern, $contents, $importMatches) === 1;
        $attributeName = $hasAttributeImport && isset($importMatches['alias']) && trim($importMatches['alias']) !== ''
            ? trim(substr(strrchr(trim($importMatches['alias']), ' '), 1))
            : 'HasUuid';
        $prepared = $contents;
        $warnings = [];
        $attributeReference = '(?:'.preg_quote($attributeName, '~').'|\\\\?'.preg_quote($attributeClass, '~').')';
        $hasAttributeUsage = preg_match('~#\[\s*'.$attributeReference.'\s*\(~', $contents) === 1;

        if ($hasAttributeUsage) {
            $attributePattern = '~(?m)^[ \t]*#\[\s*'.$attributeReference.'\s*\(\s*field\s*:\s*(?<quote>[\'\"])(?<field>[^\'\"]+)\k<quote>\s*\)\s*\][ \t]*\R(?<classIndent>[ \t]*)(?<declaration>(?:(?:final|abstract|readonly)[ \t]+)*class\b[^{}]*\{)~';
            $attributeCount = preg_match_all($attributePattern, $prepared, $attributeMatches);

            if ($attributeCount !== 1) {
                return new MigrationResult($contents, ['The KDuma HasUuid attribute could not be converted safely; migrate its field option manually.'], false);
            }

            $attributeField = $attributeMatches['field'][0] ?? null;

            if ($attributeField !== 'uuid' && preg_match('/\bfunction\s+uuidColumn\s*\(/i', $contents) === 1) {
                return new MigrationResult($contents, ['The model already defines uuidColumn(); resolve it with the KDuma field attribute manually.'], false);
            }

            $prepared = preg_replace_callback($attributePattern, static function (array $match): string {
                $field = $match['field'];
                $classIndent = $match['classIndent'];
                $declaration = $match['declaration'];

                if ($field === 'uuid') {
                    return $classIndent.$declaration;
                }

                return $classIndent.$declaration."\n\n".$classIndent."    protected function uuidColumn(): string\n".$classIndent."    {\n".$classIndent.'        return '.var_export($field, true).";\n".$classIndent."    }\n";
            }, $prepared, 1) ?? $prepared;

            $warnings[] = 'The KDuma HasUuid field attribute was converted to uuidColumn().';
        }

        if ($hasAttributeImport) {
            $prepared = preg_replace($importPattern, '', $prepared, 1) ?? $prepared;
        }

        $result = parent::migrate($prepared, $migrator);

        if (! $result->supported) {
            return new MigrationResult($contents, array_merge($warnings, $result->warnings), false);
        }

        return new MigrationResult($result->contents, array_merge($warnings, $result->warnings));
    }

    public function notes(): array
    {
        return [
            'Review generation overrides and confirm the configured field exists in the application schema.',
        ];
    }
}
