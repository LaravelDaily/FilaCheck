<?php

namespace Filacheck\Reporting;

use Filacheck\Enums\RuleCategory;
use Filacheck\Reporting\Concerns\HandlesDryRunPreviews;
use Filacheck\Rules\Rule;
use Filacheck\Support\Violation;
use Symfony\Component\Console\Output\OutputInterface;

class StandaloneReporter
{
    use HandlesDryRunPreviews;

    public function __construct(
        private OutputInterface $output,
        private bool $verbose = false,
    ) {}

    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     */
    public function report(array $rules, array $violations): void
    {
        $violations = array_values(array_filter($violations, fn (Violation $v) => ! $v->silent));

        if ($this->verbose) {
            $this->reportVerbose($rules, $violations);
        } else {
            $this->reportCompact($rules, $violations);
        }
    }

    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     */
    private function reportCompact(array $rules, array $violations): void
    {
        $violationsByRule = [];
        foreach ($violations as $violation) {
            $violationsByRule[$violation->rule][] = $violation;
        }

        $failedRules = [];
        $passCount = 0;
        $failCount = 0;

        foreach ($rules as $rule) {
            $ruleName = $rule->name();
            $ruleViolations = $violationsByRule[$ruleName] ?? [];

            if (count($ruleViolations) === 0) {
                $this->output->write('<fg=green>.</>');
                $passCount++;
            } else {
                $this->output->write('<fg=red>x</>');
                $failCount++;
                $failedRules[$ruleName] = [
                    'rule' => $rule,
                    'violations' => $ruleViolations,
                ];
            }
        }

        $this->output->writeln('');
        $this->output->writeln('');

        if (count($failedRules) > 0) {
            foreach ($failedRules as $ruleName => $data) {
                $this->reportFailedRule($ruleName, $data['rule'], $data['violations']);
            }
        }

        $this->reportSummary($violations, $passCount, $failCount);
    }

    /**
     * @param  Violation[]  $violations
     */
    private function reportFailedRule(string $ruleName, Rule $rule, array $violations): void
    {
        $categoryLabel = $rule->category()->label();

        $this->output->writeln("<fg=red>✗</> <options=bold>{$ruleName}</> <fg=gray>({$categoryLabel})</>");

        $groupedByFile = [];
        foreach ($violations as $violation) {
            $groupedByFile[$violation->file][] = $violation;
        }

        foreach ($groupedByFile as $file => $fileViolations) {
            $this->output->writeln("  <fg=gray>{$file}</>");

            foreach ($fileViolations as $violation) {
                $levelColor = match ($violation->level) {
                    'error' => 'red',
                    'warning' => 'yellow',
                    default => 'white',
                };

                $this->output->writeln(
                    "    <fg={$levelColor}>Line {$violation->line}:</> {$violation->message}"
                );

                if ($violation->suggestion) {
                    $this->output->writeln(
                        "      <fg=gray>→ {$violation->suggestion}</>"
                    );
                }
            }
        }

        $this->output->writeln('');
    }

    /**
     * @param  Violation[]  $violations
     */
    private function reportSummary(array $violations, int $passCount, int $failCount): void
    {
        $totalRules = $passCount + $failCount;

        if (count($violations) === 0) {
            $this->output->writeln("<fg=green;options=bold>All {$totalRules} rules passed!</>");

            return;
        }

        $errorCount = count(array_filter($violations, fn ($v) => $v->level === 'error'));
        $warningCount = count(array_filter($violations, fn ($v) => $v->level === 'warning'));

        $summary = [];
        if ($errorCount > 0) {
            $summary[] = "<fg=red>{$errorCount} error(s)</>";
        }
        if ($warningCount > 0) {
            $summary[] = "<fg=yellow>{$warningCount} warning(s)</>";
        }

        $rulesSummary = "<fg=green>{$passCount} passed</>, <fg=red>{$failCount} failed</>";
        $this->output->writeln("Rules: {$rulesSummary}");
        $this->output->writeln('Issues: '.implode(', ', $summary));
    }

    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     * @param  array{
     *     fixed: int,
     *     skipped: int,
     *     byFile: array<string, array{fixed: int, skipped: int}>,
     *     dryRun?: bool,
     *     previews?: array<string, array<int, array{line: int, column: int, from: string, to: string}>>
     * }  $fixResults
     */
    public function reportWithFixes(array $rules, array $violations, array $fixResults): void
    {
        $violations = array_values(array_filter($violations, fn (Violation $v) => ! $v->silent));
        $this->initializeDryRunPreviewState($fixResults);

        $violationsByRule = [];
        foreach ($violations as $violation) {
            $violationsByRule[$violation->rule][] = $violation;
        }

        $fixedRules = [];
        $unfixedRules = [];
        $passCount = 0;

        foreach ($rules as $rule) {
            $ruleName = $rule->name();
            $ruleViolations = $violationsByRule[$ruleName] ?? [];

            if (count($ruleViolations) === 0) {
                $this->output->write('<fg=green>.</>');
                $passCount++;
            } else {
                $fixableCount = count(array_filter($ruleViolations, fn ($v) => $v->isFixable));
                $unfixableCount = count($ruleViolations) - $fixableCount;

                if ($fixableCount > 0 && $unfixableCount === 0) {
                    $this->output->write('<fg=cyan>F</>');
                    $fixedRules[$ruleName] = [
                        'rule' => $rule,
                        'violations' => $ruleViolations,
                    ];
                } elseif ($fixableCount > 0) {
                    $this->output->write('<fg=yellow>P</>');
                    $unfixedRules[$ruleName] = [
                        'rule' => $rule,
                        'violations' => $ruleViolations,
                    ];
                } else {
                    $this->output->write('<fg=red>x</>');
                    $unfixedRules[$ruleName] = [
                        'rule' => $rule,
                        'violations' => $ruleViolations,
                    ];
                }
            }
        }

        $this->output->writeln('');
        $this->output->writeln('');

        $allFailedRules = $fixedRules + $unfixedRules;

        if (count($allFailedRules) > 0) {
            foreach ($allFailedRules as $ruleName => $data) {
                $this->reportFailedRuleWithFixInfo($ruleName, $data['rule'], $data['violations']);
            }
        }

        $this->reportFixSummary($fixResults, $passCount, count($rules));
    }

    /**
     * @param  Violation[]  $violations
     */
    private function reportFailedRuleWithFixInfo(string $ruleName, Rule $rule, array $violations): void
    {
        $fixableCount = count(array_filter($violations, fn ($v) => $v->isFixable));
        $unfixableCount = count($violations) - $fixableCount;
        $categoryLabel = $rule->category()->label();

        if ($fixableCount > 0 && $unfixableCount === 0) {
            $icon = '<fg=cyan>✓</>';
            $status = $this->isDryRun ? ' <fg=cyan>(proposed)</>' : ' <fg=cyan>(fixed)</>';
        } elseif ($fixableCount > 0) {
            $icon = '<fg=yellow>!</>';
            $status = $this->isDryRun ? ' <fg=yellow>(partial proposed)</>' : ' <fg=yellow>(partial fix)</>';
        } else {
            $icon = '<fg=red>✗</>';
            $status = ' <fg=red>(not fixable)</>';
        }

        $this->output->writeln("{$icon} <options=bold>{$ruleName}</> <fg=gray>({$categoryLabel})</>{$status}");

        $groupedByFile = [];
        foreach ($violations as $violation) {
            $groupedByFile[$violation->file][] = $violation;
        }

        foreach ($groupedByFile as $file => $fileViolations) {
            $this->output->writeln("  <fg=gray>{$file}</>");
            $isFirstFixableViolationInFile = true;

            foreach ($fileViolations as $violation) {
                if ($violation->isFixable) {
                    $this->output->writeln(
                        $this->isDryRun
                            ? "    <fg=cyan>Line {$violation->line}:</> {$violation->message} <fg=cyan>(proposed)</>"
                            : "    <fg=cyan>Line {$violation->line}:</> {$violation->message} <fg=cyan>(fixed)</>"
                    );
                } else {
                    $levelColor = match ($violation->level) {
                        'error' => 'red',
                        'warning' => 'yellow',
                        default => 'white',
                    };

                    $this->output->writeln(
                        "    <fg={$levelColor}>Line {$violation->line}:</> {$violation->message} <fg=yellow>(manual fix required)</>"
                    );
                }

                if ($violation->suggestion) {
                    $this->output->writeln(
                        "      <fg=gray>→ {$violation->suggestion}</>"
                    );
                }

                if ($this->isDryRun && $violation->isFixable) {
                    $previewChanges = $this->consumePreviewChanges(
                        $violation->file,
                        $violation->line,
                        includeRemainingForFile: $isFirstFixableViolationInFile
                    );

                    usort($previewChanges, function (array $left, array $right): int {
                        $lineComparison = $left['line'] <=> $right['line'];

                        if ($lineComparison !== 0) {
                            return $lineComparison;
                        }

                        return $left['column'] <=> $right['column'];
                    });

                    if (count($previewChanges) > 0) {
                        $this->output->writeln('      <fg=gray>Proposed file changes:</>');

                        foreach ($previewChanges as $change) {
                            $this->output->writeln("        <fg=gray>@@ line {$change['line']} @@</>");

                            $this->renderDryRunDiffLines($violation->file, $change);
                        }
                    }
                }

                if ($violation->isFixable) {
                    $isFirstFixableViolationInFile = false;
                }
            }
        }

        $this->output->writeln('');
    }

    /**
     * @param  array{
     *     fixed: int,
     *     skipped: int,
     *     byFile: array<string, array{fixed: int, skipped: int}>,
     *     dryRun?: bool,
     *     previews?: array<string, array<int, array{line: int, column: int, from: string, to: string}>>
     * }  $fixResults
     */
    private function reportFixSummary(array $fixResults, int $passCount, int $totalRules): void
    {
        $fixed = $fixResults['fixed'];
        $skipped = $fixResults['skipped'];
        $filesModified = count(array_filter($fixResults['byFile'], fn ($r) => $r['fixed'] > 0));
        $isDryRun = (bool) ($fixResults['dryRun'] ?? false);

        $this->output->writeln('<fg=gray>───────────────────────────────</>');

        if ($fixed > 0) {
            if ($isDryRun) {
                $this->output->writeln("<fg=cyan;options=bold>✓ Proposed {$fixed} fix(es)</> in {$filesModified} file(s)");
            } else {
                $this->output->writeln("<fg=cyan;options=bold>✓ Fixed {$fixed} issue(s)</> in {$filesModified} file(s)");
            }
        }

        if ($skipped > 0) {
            $this->output->writeln("<fg=yellow>! {$skipped} issue(s) require manual attention</>");
        }

        if ($fixed > 0 && $skipped === 0) {
            $this->output->writeln(
                $isDryRun
                    ? '<fg=green;options=bold>All fixable issues have been proposed!</>'
                    : '<fg=green;options=bold>All issues have been fixed!</>'
            );
        }
    }

    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     */
    private function reportVerbose(array $rules, array $violations): void
    {
        $violationsByRule = [];
        foreach ($violations as $violation) {
            $violationsByRule[$violation->rule][] = $violation;
        }

        $rulesByCategory = $this->groupRulesByCategory($rules);

        foreach (RuleCategory::cases() as $category) {
            $categoryRules = $rulesByCategory[$category->value] ?? [];

            if (empty($categoryRules)) {
                continue;
            }

            $this->output->writeln("<fg=cyan;options=bold>{$category->label()}</>");
            $this->output->writeln("<fg=gray>{$category->description()}</>");
            $this->output->writeln('');

            foreach ($categoryRules as $rule) {
                $ruleName = $rule->name();
                $ruleViolations = $violationsByRule[$ruleName] ?? [];
                $count = count($ruleViolations);

                if ($count === 0) {
                    $this->output->writeln("  <fg=green>✓</> {$ruleName}");
                } else {
                    $this->output->writeln("  <fg=yellow>✗</> {$ruleName} <fg=gray>({$count} finding(s))</>");
                    $this->reportRuleViolationsVerbose($ruleViolations);
                }
            }

            $this->output->writeln('');
        }

        if (count($violations) === 0) {
            $this->output->writeln('<info>No issues found!</info>');

            return;
        }

        $errorCount = count(array_filter($violations, fn ($v) => $v->level === 'error'));
        $warningCount = count(array_filter($violations, fn ($v) => $v->level === 'warning'));

        $summary = [];
        if ($errorCount > 0) {
            $summary[] = "<fg=red>{$errorCount} error(s)</>";
        }
        if ($warningCount > 0) {
            $summary[] = "<fg=yellow>{$warningCount} warning(s)</>";
        }

        $this->output->writeln('Found '.implode(' and ', $summary).'.');
    }

    /**
     * @param  Rule[]  $rules
     * @return array<string, Rule[]>
     */
    private function groupRulesByCategory(array $rules): array
    {
        $grouped = [];

        foreach ($rules as $rule) {
            $category = $rule->category()->value;
            $grouped[$category][] = $rule;
        }

        return $grouped;
    }

    /**
     * @param  Violation[]  $violations
     */
    private function reportRuleViolationsVerbose(array $violations): void
    {
        $groupedByFile = [];
        foreach ($violations as $violation) {
            $groupedByFile[$violation->file][] = $violation;
        }

        foreach ($groupedByFile as $file => $fileViolations) {
            $this->output->writeln("    <fg=gray>{$file}</>");

            foreach ($fileViolations as $violation) {
                $levelColor = match ($violation->level) {
                    'error' => 'red',
                    'warning' => 'yellow',
                    default => 'white',
                };

                $this->output->writeln(
                    "      <fg={$levelColor}>Line {$violation->line}:</> {$violation->message}"
                );

                if ($violation->suggestion) {
                    $this->output->writeln(
                        "        <fg=gray>→ {$violation->suggestion}</>"
                    );
                }
            }
        }
    }
}
