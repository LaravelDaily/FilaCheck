<?php

namespace Filacheck\Fixer;

use Filacheck\Support\Violation;

class CodeFixer
{
    /** @var array<string, array{fixed: int, skipped: int}> */
    private array $results = [];

    /** @var array<string, array<int, array{line: int, column: int, from: string, to: string}>> */
    private array $previews = [];

    private int $totalFixed = 0;

    private int $totalSkipped = 0;

    /**
     * Apply fixes from violations to files.
     *
     * @param  Violation[]  $violations
     * @return array{
     *     fixed: int,
     *     skipped: int,
     *     byFile: array<string, array{fixed: int, skipped: int}>,
     *     dryRun: bool,
     *     previews: array<string, array<int, array{line: int, column: int, from: string, to: string}>>
     * }
     */
    public function fix(array $violations, bool $createBackup = false, bool $dryRun = false): array
    {
        $this->results = [];
        $this->previews = [];
        $this->totalFixed = 0;
        $this->totalSkipped = 0;

        $violationsByFile = $this->groupByFile($violations);

        foreach ($violationsByFile as $file => $fileViolations) {
            $this->fixFile($file, $fileViolations, $createBackup, $dryRun);
        }

        return [
            'fixed' => $this->totalFixed,
            'skipped' => $this->totalSkipped,
            'byFile' => $this->results,
            'dryRun' => $dryRun,
            'previews' => $this->previews,
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
    private function fixFile(string $file, array $violations, bool $createBackup, bool $dryRun): void
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

        $this->previews[$file] = [];

        foreach ($fixableViolations as $violation) {
            $lineStartPosition = strrpos(substr($content, 0, $violation->startPos), "\n");
            $lineStartPosition = $lineStartPosition === false ? 0 : $lineStartPosition + 1;

            $this->previews[$file][] = [
                'line' => $violation->line,
                'column' => $violation->startPos - $lineStartPosition + 1,
                'from' => (string) substr($content, $violation->startPos, $violation->endPos - $violation->startPos),
                'to' => (string) $violation->replacement,
            ];
        }

        // Apply replacements from end to beginning
        foreach ($fixableViolations as $violation) {
            $content = substr_replace(
                $content,
                $violation->replacement,
                $violation->startPos,
                $violation->endPos - $violation->startPos
            );
        }

        if ($createBackup && ! $dryRun) {
            copy($file, $file . '.bak');
        }

        if (! $dryRun) {
            file_put_contents($file, $content);
        }

        $fixed = count($fixableViolations);
        $this->totalFixed += $fixed;
        $this->results[$file] = ['fixed' => $fixed, 'skipped' => $skipped];
    }
}
