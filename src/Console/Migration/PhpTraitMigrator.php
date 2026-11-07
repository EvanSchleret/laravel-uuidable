<?php

namespace EvanSchleret\LaravelUuidable\Console\Migration;

final class PhpTraitMigrator
{
    public function migrate(string $contents, array $traitMappings): MigrationResult
    {
        $mappings = [];

        foreach ($traitMappings as $source => $target) {
            $mappings[$this->normalizeName($source)] = ltrim($target, '\\');
        }

        $tokens = token_get_all($contents);
        $aliases = [];
        $warnings = [];
        $supported = true;
        $braceDepth = 0;
        $classBodyDepths = [];
        $pendingClassBody = false;
        $previousSignificantId = null;

        foreach ($tokens as $index => $token) {
            $id = is_array($token) ? $token[0] : null;
            $text = is_array($token) ? $token[1] : $token;

            if ($id === T_CLASS && $previousSignificantId !== T_DOUBLE_COLON) {
                $pendingClassBody = true;
            } elseif (in_array($id, [T_TRAIT, T_INTERFACE, T_ENUM], true)) {
                $pendingClassBody = true;
            }

            if ($id === T_USE) {
                $nextIndex = $this->nextMeaningfulIndex($tokens, $index + 1);
                $nextText = $nextIndex === null ? null : $this->tokenText($tokens[$nextIndex]);
                $inClassBody = $classBodyDepths !== [] && end($classBodyDepths) === $braceDepth;

                if ($nextText === '(') {
                    continue;
                }

                if ($inClassBody) {
                    for ($cursor = $nextIndex; $cursor !== null; $cursor++) {
                        $current = $tokens[$cursor] ?? null;

                        if ($current === null || $current === ';' || $current === '{') {
                            break;
                        }

                        if (! is_array($current)) {
                            continue;
                        }

                        $normalized = $this->normalizeName($current[1]);

                        if (isset($mappings[$normalized])) {
                            $tokens[$cursor][1] = '\\'.$mappings[$normalized];
                        } elseif ($current[0] === T_STRING && isset($aliases[strtolower($current[1])])) {
                            $tokens[$cursor][1] = $aliases[strtolower($current[1])];
                        }
                    }
                } else {
                    $endIndex = $this->statementEndIndex($tokens, $nextIndex);

                    if ($endIndex === null) {
                        $previousSignificantId = $id;

                        continue;
                    }

                    $groupImport = false;

                    for ($cursor = $nextIndex; $cursor < $endIndex; $cursor++) {
                        if (in_array(($tokens[$cursor] ?? null), ['{', ','], true)) {
                            $groupImport = true;
                            break;
                        }
                    }

                    if ($groupImport) {
                        $statement = $this->tokensToString(array_slice($tokens, $index, $endIndex - $index + 1));

                        foreach (array_keys($mappings) as $source) {
                            $separator = strrpos($source, '\\');
                            $namespace = $separator === false ? '' : substr($source, 0, $separator);
                            $shortName = $separator === false ? $source : substr($source, $separator + 1);
                            $isMappedGroupImport = str_contains(strtolower($statement), $source)
                                || ($namespace !== ''
                                    && str_contains(strtolower($statement), strtolower($namespace.'\\{'))
                                    && str_contains(strtolower($statement), $shortName));

                            if ($isMappedGroupImport) {
                                $warnings[] = 'Grouped use imports are not rewritten automatically.';
                                $supported = false;
                                break;
                            }
                        }
                    } else {
                        $nameIndex = $this->nextMeaningfulIndex($tokens, $index + 1);
                        $sourceName = $nameIndex === null ? null : $this->normalizeName($this->tokenText($tokens[$nameIndex]));

                        if ($sourceName !== null && isset($mappings[$sourceName])) {
                            $aliasIndex = $this->nextMeaningfulIndex($tokens, $nameIndex + 1);
                            $hasAlias = $aliasIndex !== null && is_array($tokens[$aliasIndex]) && $tokens[$aliasIndex][0] === T_AS;

                            if (! $hasAlias) {
                                $sourceShortName = strtolower(substr($sourceName, strrpos($sourceName, '\\') + 1));
                                $targetShortName = substr($mappings[$sourceName], strrpos($mappings[$sourceName], '\\') + 1);
                                $aliases[$sourceShortName] = $targetShortName;
                            }

                            $tokens[$nameIndex][1] = $mappings[$sourceName];
                        }
                    }

                    $index = $endIndex;
                }
            }

            if ($text === '{') {
                $braceDepth++;

                if ($pendingClassBody) {
                    $classBodyDepths[] = $braceDepth;
                    $pendingClassBody = false;
                }
            } elseif ($text === '}') {
                if ($classBodyDepths !== [] && end($classBodyDepths) === $braceDepth) {
                    array_pop($classBodyDepths);
                }

                $braceDepth--;
            }

            if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
                $previousSignificantId = $id;
            }
        }

        if (! $supported) {
            return new MigrationResult($contents, $warnings, false);
        }

        return new MigrationResult($this->tokensToString($tokens), $warnings);
    }

    private function nextMeaningfulIndex(array $tokens, int $start): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];
            $id = is_array($token) ? $token[0] : null;

            if (! in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $index;
            }
        }

        return null;
    }

    private function statementEndIndex(array $tokens, int $start): ?int
    {
        for ($index = $start, $count = count($tokens); $index < $count; $index++) {
            if (($tokens[$index] ?? null) === ';') {
                return $index;
            }
        }

        return null;
    }

    private function tokenText(array|string $token): string
    {
        return is_array($token) ? $token[1] : $token;
    }

    private function normalizeName(string $name): string
    {
        return strtolower(ltrim($name, '\\'));
    }

    private function tokensToString(array $tokens): string
    {
        return implode('', array_map(fn (array|string $token): string => $this->tokenText($token), $tokens));
    }
}
