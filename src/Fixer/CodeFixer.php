<?php

namespace Filacheck\Fixer;

use Filacheck\Support\Violation;

class CodeFixer
{
    /** @var array<string, array{fixed: int, skipped: int}> */
    private array $results = [];

    private int $totalFixed = 0;

    private int $totalSkipped = 0;

    /**
     * Apply fixes from violations to files.
     *
     * @param  Violation[]  $violations
     * @return array{fixed: int, skipped: int, byFile: array<string, array{fixed: int, skipped: int}>}
     */
    public function fix(array $violations, bool $createBackup = false): array
    {
        $this->results = [];
        $this->totalFixed = 0;
        $this->totalSkipped = 0;

        $violationsByFile = $this->groupByFile($violations);

        foreach ($violationsByFile as $file => $fileViolations) {
            $this->fixFile($file, $fileViolations, $createBackup);
        }

        return [
            'fixed' => $this->totalFixed,
            'skipped' => $this->totalSkipped,
            'byFile' => $this->results,
        ];
    }

    /**
     * @param  Violation[]  $violations
     * @return array<string, Violation[]>
     */
    private function groupByFile(array $violations): array
    {
        $grouped = [];

        foreach ($violations as $violation) {
            $grouped[$violation->file][] = $violation;
        }

        return $grouped;
    }

    /**
     * @param  Violation[]  $violations
     */
    private function fixFile(string $file, array $violations, bool $createBackup): void
    {
        if (! file_exists($file)) {
            return;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return;
        }

        $fixableViolations = array_filter(
            $violations,
            fn (Violation $v) => $v->isFixable && $v->startPos !== null && $v->endPos !== null && $v->replacement !== null
        );

        $skipped = count($violations) - count($fixableViolations);
        $this->totalSkipped += $skipped;

        if (count($fixableViolations) === 0) {
            $this->results[$file] = ['fixed' => 0, 'skipped' => $skipped];

            return;
        }

        // Sort by position in reverse order to avoid offset shifts
        usort($fixableViolations, fn (Violation $a, Violation $b) => $b->startPos <=> $a->startPos);

        // Apply replacements from end to beginning
        foreach ($fixableViolations as $violation) {
            $content = substr_replace(
                $content,
                $violation->replacement,
                $violation->startPos,
                $violation->endPos - $violation->startPos
            );
        }

        if ($createBackup) {
            copy($file, $file.'.bak');
        }

        file_put_contents($file, $content);

        $fixed = count($fixableViolations);
        $this->totalFixed += $fixed;
        $this->results[$file] = ['fixed' => $fixed, 'skipped' => $skipped];
    }
}
