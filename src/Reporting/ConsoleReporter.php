<?php

namespace Filacheck\Reporting;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Rule;
use Filacheck\Support\Violation;
use Illuminate\Console\Command;

class ConsoleReporter
{
    public function __construct(
        private Command $command,
        private bool $verbose = false,
    ) {}

    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     */
    public function report(array $rules, array $violations): void
    {
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
                $this->command->getOutput()->write('<fg=green>.</>');
                $passCount++;
            } else {
                $this->command->getOutput()->write('<fg=red>x</>');
                $failCount++;
                $failedRules[$ruleName] = [
                    'rule' => $rule,
                    'violations' => $ruleViolations,
                ];
            }
        }

        $this->command->newLine(2);

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
        $count = count($violations);
        $categoryLabel = $rule->category()->label();

        $this->command->line("<fg=red>✗</> <options=bold>{$ruleName}</> <fg=gray>({$categoryLabel})</>");

        $groupedByFile = [];
        foreach ($violations as $violation) {
            $groupedByFile[$violation->file][] = $violation;
        }

        foreach ($groupedByFile as $file => $fileViolations) {
            $this->command->line("  <fg=gray>{$file}</>");

            foreach ($fileViolations as $violation) {
                $levelColor = match ($violation->level) {
                    'error' => 'red',
                    'warning' => 'yellow',
                    default => 'white',
                };

                $this->command->line(
                    "    <fg={$levelColor}>Line {$violation->line}:</> {$violation->message}"
                );

                if ($violation->suggestion) {
                    $this->command->line(
                        "      <fg=gray>→ {$violation->suggestion}</>"
                    );
                }
            }
        }

        $this->command->newLine();
    }

    /**
     * @param  Violation[]  $violations
     */
    private function reportSummary(array $violations, int $passCount, int $failCount): void
    {
        $totalRules = $passCount + $failCount;

        if (count($violations) === 0) {
            $this->command->line("<fg=green;options=bold>All {$totalRules} rules passed!</>");

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
        $this->command->line("Rules: {$rulesSummary}");
        $this->command->line('Issues: '.implode(', ', $summary));
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

            $this->command->line("<fg=cyan;options=bold>{$category->label()}</>");
            $this->command->line("<fg=gray>{$category->description()}</>");
            $this->command->newLine();

            foreach ($categoryRules as $rule) {
                $ruleName = $rule->name();
                $ruleViolations = $violationsByRule[$ruleName] ?? [];
                $count = count($ruleViolations);

                if ($count === 0) {
                    $this->command->line("  <fg=green>✓</> {$ruleName}");
                } else {
                    $this->command->line("  <fg=yellow>✗</> {$ruleName} <fg=gray>({$count} finding(s))</>");
                    $this->reportRuleViolationsVerbose($ruleViolations);
                }
            }

            $this->command->newLine();
        }

        if (count($violations) === 0) {
            $this->command->info('No issues found!');

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

        $this->command->line('Found '.implode(' and ', $summary).'.');
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
            $this->command->line("    <fg=gray>{$file}</>");

            foreach ($fileViolations as $violation) {
                $levelColor = match ($violation->level) {
                    'error' => 'red',
                    'warning' => 'yellow',
                    default => 'white',
                };

                $this->command->line(
                    "      <fg={$levelColor}>Line {$violation->line}:</> {$violation->message}"
                );

                if ($violation->suggestion) {
                    $this->command->line(
                        "        <fg=gray>→ {$violation->suggestion}</>"
                    );
                }
            }
        }
    }

    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     * @param  array{fixed: int, skipped: int, byFile: array<string, array{fixed: int, skipped: int}>}  $fixResults
     */
    public function reportWithFixes(array $rules, array $violations, array $fixResults): void
    {
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
                $this->command->getOutput()->write('<fg=green>.</>');
                $passCount++;
            } else {
                $fixableCount = count(array_filter($ruleViolations, fn ($v) => $v->isFixable));
                $unfixableCount = count($ruleViolations) - $fixableCount;

                if ($fixableCount > 0 && $unfixableCount === 0) {
                    $this->command->getOutput()->write('<fg=cyan>F</>');
                    $fixedRules[$ruleName] = [
                        'rule' => $rule,
                        'violations' => $ruleViolations,
                    ];
                } elseif ($fixableCount > 0) {
                    $this->command->getOutput()->write('<fg=yellow>P</>');
                    $unfixedRules[$ruleName] = [
                        'rule' => $rule,
                        'violations' => $ruleViolations,
                    ];
                } else {
                    $this->command->getOutput()->write('<fg=red>x</>');
                    $unfixedRules[$ruleName] = [
                        'rule' => $rule,
                        'violations' => $ruleViolations,
                    ];
                }
            }
        }

        $this->command->newLine(2);

        if (count($fixedRules) > 0) {
            $this->command->line('<fg=cyan;options=bold>Fixed:</>');
            foreach ($fixedRules as $ruleName => $data) {
                $count = count($data['violations']);
                $this->command->line("  <fg=cyan>✓</> {$ruleName} <fg=gray>({$count} fixed)</>");
            }
            $this->command->newLine();
        }

        if (count($unfixedRules) > 0) {
            $this->command->line('<fg=yellow;options=bold>Remaining issues:</>');
            foreach ($unfixedRules as $ruleName => $data) {
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
        $fixable = array_filter($violations, fn ($v) => $v->isFixable);
        $unfixable = array_filter($violations, fn ($v) => ! $v->isFixable);

        $fixedCount = count($fixable);
        $unfixedCount = count($unfixable);

        $status = $unfixedCount > 0
            ? ($fixedCount > 0 ? '<fg=yellow>partial</>' : '<fg=red>not fixable</>')
            : '<fg=cyan>fixed</>';

        $this->command->line("  <fg=yellow>!</> {$ruleName} ({$status})");

        if ($unfixedCount > 0) {
            $groupedByFile = [];
            foreach ($unfixable as $violation) {
                $groupedByFile[$violation->file][] = $violation;
            }

            foreach ($groupedByFile as $file => $fileViolations) {
                $this->command->line("    <fg=gray>{$file}</>");

                foreach ($fileViolations as $violation) {
                    $this->command->line(
                        "      <fg=yellow>Line {$violation->line}:</> {$violation->message} <fg=gray>(manual fix required)</>"
                    );

                    if ($violation->suggestion) {
                        $this->command->line(
                            "        <fg=gray>→ {$violation->suggestion}</>"
                        );
                    }
                }
            }
        }

        $this->command->newLine();
    }

    /**
     * @param  array{fixed: int, skipped: int, byFile: array<string, array{fixed: int, skipped: int}>}  $fixResults
     */
    private function reportFixSummary(array $fixResults, int $passCount, int $totalRules): void
    {
        $fixed = $fixResults['fixed'];
        $skipped = $fixResults['skipped'];
        $filesModified = count(array_filter($fixResults['byFile'], fn ($r) => $r['fixed'] > 0));

        $this->command->line('<fg=gray>───────────────────────────────</>');

        if ($fixed > 0) {
            $this->command->line("<fg=cyan;options=bold>✓ Fixed {$fixed} issue(s)</> in {$filesModified} file(s)");
        }

        if ($skipped > 0) {
            $this->command->line("<fg=yellow>! {$skipped} issue(s) require manual attention</>");
        }

        if ($fixed > 0 && $skipped === 0) {
            $this->command->line('<fg=green;options=bold>All issues have been fixed!</>');
        }
    }
}
