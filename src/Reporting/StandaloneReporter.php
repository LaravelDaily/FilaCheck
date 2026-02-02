<?php

namespace Filacheck\Reporting;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Rule;
use Filacheck\Support\Violation;
use Symfony\Component\Console\Output\OutputInterface;

class StandaloneReporter
{
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
