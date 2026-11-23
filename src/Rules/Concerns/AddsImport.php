<?php

namespace Filacheck\Rules\Concerns;

use Filacheck\Support\Context;
use Filacheck\Support\Violation;

trait AddsImport
{
    /** @var array<string, array<string, string>> file => [lowercased alias => class] */
    private array $importAddedForFiles = [];

    /**
     * Return the class the file imports under the short name of `$class` when
     * that is an unrelated class, so a fix can't refer to `$class` by its short
     * name. Returns null when the short name is free, already imports `$class`,
     * or imports one of the `$compatible` classes (for example a legacy import
     * the same fix renames to `$class`).
     *
     * @param  string[]  $compatible
     */
    protected function conflictingImport(string $class, Context $context, array $compatible = []): ?string
    {
        $imported = $this->importedClass($this->shortName($class), $context);

        if ($imported === null) {
            return null;
        }

        foreach ([$class, ...$compatible] as $candidate) {
            if (strcasecmp(ltrim($candidate, '\\'), $imported) === 0) {
                return null;
            }
        }

        return $imported;
    }

    /**
     * Build a silent violation that inserts `use $class;` when nothing in the
     * file is imported under its short name yet. Returns null when the short
     * name is already taken, so check `conflictingImport()` first.
     *
     * Also accepts a full `use Foo\Bar;` statement, the format this method
     * took before, so rules written against it keep working.
     */
    protected function buildImportViolation(string $class, Context $context): ?Violation
    {
        if (preg_match('/^use\s+([^;\s]+)\s*;$/', trim($class), $match)) {
            $class = $match[1];
        }

        $class = ltrim($class, '\\');
        $alias = $this->shortName($class);

        if ($this->importedClass($alias, $context) !== null) {
            return null;
        }

        $this->importAddedForFiles[$context->file][strtolower($alias)] = $class;

        $useStatement = "use {$class};";
        $importStatement = $useStatement . "\n";

        if (preg_match_all('/^use\s+[^;]+;[ \t]*\n/m', $context->code, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $insertionPoint = $lastMatch[1] + strlen($lastMatch[0]);
        } elseif (preg_match('/^<\?php\s*/m', $context->code, $match, PREG_OFFSET_CAPTURE)) {
            $insertionPoint = $match[0][1] + strlen($match[0][0]);
            $importStatement = "\n" . $importStatement;
        } else {
            $insertionPoint = 0;
        }

        return new Violation(
            level: 'warning',
            message: "Missing import: {$useStatement}",
            file: $context->file,
            line: 1,
            isFixable: true,
            startPos: $insertionPoint,
            endPos: $insertionPoint,
            replacement: $importStatement,
            silent: true,
        );
    }

    /**
     * The file's import under the short name of `$replacement`, when it is a plain
     * `use` of `$legacyClass` that a fix can rename in place to `$replacement`.
     * Group imports can't be renamed in place, so they return null.
     *
     * @return array{name: string, alias: string, startPos: int, endPos: int}|null
     */
    protected function renameableLegacyImport(string $legacyClass, string $replacement, Context $context): ?array
    {
        $import = $context->imports[strtolower($this->shortName($replacement))] ?? null;

        if (
            $import === null
            || strcasecmp($import['name'], ltrim($legacyClass, '\\')) !== 0
            || $import['startPos'] === null
            || $import['endPos'] === null
        ) {
            return null;
        }

        return $import;
    }

    private function importedClass(string $alias, Context $context): ?string
    {
        $key = strtolower($alias);

        return $context->imports[$key]['name']
            ?? $this->importAddedForFiles[$context->file][$key]
            ?? null;
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
