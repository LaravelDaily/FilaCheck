<?php

namespace Filacheck\Fixer;

use Filacheck\Support\Violation;
use Symfony\Component\Process\Process;

class CodeFixer
{
    /** @var array<string, array{fixed: int, skipped: int}> */
    private array $results = [];

    /** @var array<string, array<int, array{line: int, column: int, from: string, to: string}>> */
    private array $previews = [];

    /** @var array<int, array{command: string, cwd: string, status: string, output: string, violations: int}> */
    private array $commands = [];

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
     *     previews: array<string, array<int, array{line: int, column: int, from: string, to: string}>>,
     *     commands: array<int, array{command: string, cwd: string, status: string, output: string, violations: int}>
     * }
     */
    public function fix(array $violations, bool $createBackup = false, bool $dryRun = false): array
    {
        $this->results = [];
        $this->previews = [];
        $this->commands = [];
        $this->totalFixed = 0;
        $this->totalSkipped = 0;

        [$commandViolations, $textViolations] = $this->partition($violations);

        $violationsByFile = $this->groupByFile($textViolations);

        foreach ($violationsByFile as $file => $fileViolations) {
            $this->fixFile($file, $fileViolations, $createBackup, $dryRun);
        }

        $this->fixCommands($commandViolations, $dryRun);

        return [
            'fixed' => $this->totalFixed,
            'skipped' => $this->totalSkipped,
            'byFile' => $this->results,
            'dryRun' => $dryRun,
            'previews' => $this->previews,
            'commands' => $this->commands,
        ];
    }

    /**
     * Split violations into command-style (run a shell command) and text-style
     * (byte-level replacement). A violation is command-style when it carries
     * a non-null fixCommand; otherwise it falls through to the text path so
     * all existing fixable rules keep behaving identically.
     *
     * @param  Violation[]  $violations
     * @return array{0: Violation[], 1: Violation[]}
     */
    private function partition(array $violations): array
    {
        $command = [];
        $text = [];

        foreach ($violations as $violation) {
            if ($violation->fixCommand !== null) {
                $command[] = $violation;
            } else {
                $text[] = $violation;
            }
        }

        return [$command, $text];
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

    /**
     * Execute command-style fix violations. Multiple violations carrying the
     * same (fixCommand, fixCommandCwd) tuple collapse into a single execution
     * but still each contribute to the fixed/skipped counters so the user
     * sees the same scale they'd see with text-style fixes. Refuses to run
     * when the resolved cwd does not contain an `artisan` file — that almost
     * always indicates a misconfigured rule passing the wrong basePath.
     *
     * @param  Violation[]  $violations
     */
    private function fixCommands(array $violations, bool $dryRun): void
    {
        if ($violations === []) {
            return;
        }

        // Group originating violations by their (command, cwd) tuple so a
        // command runs once but counts once per blade file that triggered it.
        $groups = [];

        foreach ($violations as $violation) {
            if (! $violation->isFixable || $violation->fixCommand === null) {
                $this->totalSkipped++;

                continue;
            }

            $cwd = $violation->fixCommandCwd ?? (getcwd() ?: '');
            $key = $violation->fixCommand . '|' . $cwd;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'command' => $violation->fixCommand,
                    'cwd' => $cwd,
                    'count' => 0,
                ];
            }

            $groups[$key]['count']++;
        }

        foreach ($groups as $group) {
            $command = $group['command'];
            $cwd = $group['cwd'];
            $count = $group['count'];

            if ($cwd === '' || ! is_dir($cwd) || ! is_file($cwd . DIRECTORY_SEPARATOR . 'artisan')) {
                $this->commands[] = [
                    'command' => $command,
                    'cwd' => $cwd,
                    'status' => 'failed',
                    'output' => 'Refusing to run: no artisan file found in working directory.',
                    'violations' => $count,
                ];
                $this->totalSkipped += $count;

                continue;
            }

            if ($dryRun) {
                $this->commands[] = [
                    'command' => $command,
                    'cwd' => $cwd,
                    'status' => 'would-run',
                    'output' => '',
                    'violations' => $count,
                ];
                $this->totalFixed += $count;

                continue;
            }

            $process = Process::fromShellCommandline($command, $cwd, null, null, 60);

            try {
                $process->run();
            } catch (\Throwable $e) {
                $this->commands[] = [
                    'command' => $command,
                    'cwd' => $cwd,
                    'status' => 'failed',
                    'output' => $e->getMessage(),
                    'violations' => $count,
                ];
                $this->totalSkipped += $count;

                continue;
            }

            if ($process->isSuccessful()) {
                $this->commands[] = [
                    'command' => $command,
                    'cwd' => $cwd,
                    'status' => 'ran',
                    'output' => trim($process->getOutput()),
                    'violations' => $count,
                ];
                $this->totalFixed += $count;
            } else {
                $output = trim($process->getErrorOutput());
                if ($output === '') {
                    $output = trim($process->getOutput());
                }

                $this->commands[] = [
                    'command' => $command,
                    'cwd' => $cwd,
                    'status' => 'failed',
                    'output' => $output,
                    'violations' => $count,
                ];
                $this->totalSkipped += $count;
            }
        }
    }
}
