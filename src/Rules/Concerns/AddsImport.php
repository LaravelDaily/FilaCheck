<?php

namespace Filacheck\Rules\Concerns;

use Filacheck\Support\Context;
use Filacheck\Support\Violation;

trait AddsImport
{
    private array $importAddedForFiles = [];

    protected function buildImportViolation(string $useStatement, Context $context): ?Violation
    {
        if (
            str_contains($context->code, $useStatement)
            || isset($this->importAddedForFiles[$context->file])
        ) {
            return null;
        }

        $this->importAddedForFiles[$context->file] = true;

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
}
