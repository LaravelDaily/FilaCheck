<?php

use Filacheck\Enums\RuleCategory;
use Filacheck\Reporting\StandaloneReporter;
use Filacheck\Rules\Rule;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use Symfony\Component\Console\Output\BufferedOutput;

function withTempDir(callable $callback): void
{
    $tempDir = sys_get_temp_dir() . '/filacheck-reporter-test-' . uniqid('', true) . '-' . getmypid();
    mkdir($tempDir, 0755, true);

    try {
        $callback($tempDir);
    } finally {
        $files = glob($tempDir . '/*');

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($tempDir);
    }
}

function createRule(string $name): Rule
{
    return new class($name) implements Rule
    {
        public function __construct(private string $name) {}

        public function name(): string
        {
            return $this->name;
        }

        public function category(): RuleCategory
        {
            return RuleCategory::Deprecated;
        }

        public function check(Node $node, Context $context): array
        {
            return [];
        }
    };
}

function writeFileWithLines(string $path, int $lineCount): void
{
    $lines = [];

    for ($line = 1; $line <= $lineCount; $line++) {
        $lines[] = "line {$line}";
    }

    file_put_contents($path, implode("\n", $lines) . "\n");
}

function renderDryRunOutput(array $rules, array $violations, array $previews, array $byFile): string
{
    $output = new BufferedOutput;
    $reporter = new StandaloneReporter($output);

    $reporter->reportWithFixes($rules, $violations, [
        'fixed' => array_sum(array_map(fn (array $result): int => $result['fixed'], $byFile)),
        'skipped' => 0,
        'byFile' => $byFile,
        'dryRun' => true,
        'previews' => $previews,
    ]);

    return $output->fetch();
}

it('shows a single diff for a single violation', function () {
    withTempDir(function (string $tempDir) {
        $file = $tempDir . '/SingleDiff.php';
        writeFileWithLines($file, 10);

        $rule = createRule('dry-run-single');
        $violations = [
            new Violation(
                level: 'warning',
                message: 'The `reactive()` method is deprecated.',
                file: $file,
                line: 7,
                suggestion: 'Use `live()` instead of `reactive()`.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 100,
                endPos: 108,
                replacement: 'live()',
            ),
        ];

        $output = renderDryRunOutput(
            rules: [$rule],
            violations: $violations,
            previews: [
                $file => [
                    ['line' => 7, 'column' => 1, 'from' => 'line 7', 'to' => 'line 7 updated'],
                ],
            ],
            byFile: [$file => ['fixed' => 1, 'skipped' => 0]],
        );

        expect(substr_count($output, 'Proposed file changes:'))->toBe(1)
            ->and($output)->toContain('@@ line 7 @@');
    });
});

it('shows multiple diffs for multiple violations in the same file', function () {
    withTempDir(function (string $tempDir) {
        $file = $tempDir . '/SameFile.php';
        writeFileWithLines($file, 20);

        $rule = createRule('dry-run-multi-same-file');
        $violations = [
            new Violation(
                level: 'warning',
                message: 'First fixable issue.',
                file: $file,
                line: 5,
                suggestion: 'Apply first fix.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 50,
                endPos: 57,
                replacement: 'first',
            ),
            new Violation(
                level: 'warning',
                message: 'Second fixable issue.',
                file: $file,
                line: 12,
                suggestion: 'Apply second fix.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 120,
                endPos: 128,
                replacement: 'second',
            ),
        ];

        $output = renderDryRunOutput(
            rules: [$rule],
            violations: $violations,
            previews: [
                $file => [
                    ['line' => 12, 'column' => 1, 'from' => 'line 12', 'to' => 'line 12 updated'],
                    ['line' => 5, 'column' => 1, 'from' => 'line 5', 'to' => 'line 5 updated'],
                ],
            ],
            byFile: [$file => ['fixed' => 2, 'skipped' => 0]],
        );

        expect(substr_count($output, 'Proposed file changes:'))->toBe(2)
            ->and($output)->toContain('@@ line 5 @@')
            ->and($output)->toContain('@@ line 12 @@');
    });
});

it('shows diffs for violations across different files', function () {
    withTempDir(function (string $tempDir) {
        $firstFile = $tempDir . '/FirstFile.php';
        $secondFile = $tempDir . '/SecondFile.php';
        writeFileWithLines($firstFile, 8);
        writeFileWithLines($secondFile, 8);

        $rule = createRule('dry-run-multi-files');
        $violations = [
            new Violation(
                level: 'warning',
                message: 'First file issue.',
                file: $firstFile,
                line: 3,
                suggestion: 'Fix first file.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 30,
                endPos: 36,
                replacement: 'first',
            ),
            new Violation(
                level: 'warning',
                message: 'Second file issue.',
                file: $secondFile,
                line: 6,
                suggestion: 'Fix second file.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 60,
                endPos: 67,
                replacement: 'second',
            ),
        ];

        $output = renderDryRunOutput(
            rules: [$rule],
            violations: $violations,
            previews: [
                $firstFile => [
                    ['line' => 3, 'column' => 1, 'from' => 'line 3', 'to' => 'line 3 updated'],
                ],
                $secondFile => [
                    ['line' => 6, 'column' => 1, 'from' => 'line 6', 'to' => 'line 6 updated'],
                ],
            ],
            byFile: [
                $firstFile => ['fixed' => 1, 'skipped' => 0],
                $secondFile => ['fixed' => 1, 'skipped' => 0],
            ],
        );

        expect(substr_count($output, 'Proposed file changes:'))->toBe(2)
            ->and($output)->toContain($firstFile)
            ->and($output)->toContain($secondFile);
    });
});

it('shows a single diff block with multiple line changes', function () {
    withTempDir(function (string $tempDir) {
        $file = $tempDir . '/SingleViolationMultiLineDiff.php';
        writeFileWithLines($file, 100);

        $rule = createRule('dry-run-single-violation-multi-line');
        $violations = [
            new Violation(
                level: 'warning',
                message: 'Fixable issue with import addition.',
                file: $file,
                line: 78,
                suggestion: 'Apply replacement and add import.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 780,
                endPos: 788,
                replacement: 'updated',
            ),
        ];

        $output = renderDryRunOutput(
            rules: [$rule],
            violations: $violations,
            previews: [
                $file => [
                    ['line' => 78, 'column' => 1, 'from' => 'line 78', 'to' => 'line 78 updated'],
                    ['line' => 1, 'column' => 1, 'from' => '', 'to' => 'use App\\Support\\Example;'],
                ],
            ],
            byFile: [$file => ['fixed' => 2, 'skipped' => 0]],
        );

        $lineOnePosition = strpos($output, '@@ line 1 @@');
        $lineSeventyEightPosition = strpos($output, '@@ line 78 @@');

        expect(substr_count($output, 'Proposed file changes:'))->toBe(1)
            ->and($lineOnePosition)->not->toBeFalse()
            ->and($lineSeventyEightPosition)->not->toBeFalse()
            ->and($lineOnePosition)->toBeLessThan($lineSeventyEightPosition)
            ->and($output)->not->toContain('Additional proposed file changes:');
    });
});

it('shows all lines for a multiline replacement preview', function () {
    withTempDir(function (string $tempDir) {
        $file = $tempDir . '/MultilineReplacement.php';
        file_put_contents($file, implode("\n", [
            '<?php',
            '',
            '$component',
            '    ->assertFormFieldHidden(',
            "        'hidden_title'",
            '    );',
            '',
        ]));

        $rule = createRule('dry-run-multiline-replacement');
        $violations = [
            new Violation(
                level: 'warning',
                message: 'The `assertFormFieldHidden()` method is deprecated.',
                file: $file,
                line: 4,
                suggestion: 'Use `assertSchemaComponentHidden(..., \'form\')` instead.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 0,
                endPos: 0,
                replacement: 'unused',
            ),
        ];

        $output = renderDryRunOutput(
            rules: [$rule],
            violations: $violations,
            previews: [
                $file => [
                    [
                        'line' => 4,
                        'column' => 5,
                        'from' => implode("\n", [
                            '->assertFormFieldHidden(',
                            "    'hidden_title'",
                            ');',
                        ]),
                        'to' => implode("\n", [
                            '->assertSchemaComponentHidden(',
                            "    'hidden_title',",
                            "    'form',",
                            ');',
                        ]),
                    ],
                ],
            ],
            byFile: [$file => ['fixed' => 1, 'skipped' => 0]],
        );

        expect($output)->toContain('@@ line 4 @@')
            ->and($output)->toContain('- ->assertFormFieldHidden(')
            ->and($output)->toContain("-     'hidden_title'")
            ->and($output)->toContain('+ ->assertSchemaComponentHidden(')
            ->and($output)->toContain("+     'hidden_title',")
            ->and($output)->toContain("+     'form',");
    });
});

it('sorts multiple diffs by line number', function () {
    withTempDir(function (string $tempDir) {
        $file = $tempDir . '/SortedDiffs.php';
        writeFileWithLines($file, 100);

        $rule = createRule('dry-run-sorted-lines');
        $violations = [
            new Violation(
                level: 'warning',
                message: 'Later line issue.',
                file: $file,
                line: 78,
                suggestion: 'Fix later line.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 780,
                endPos: 786,
                replacement: 'later',
            ),
            new Violation(
                level: 'warning',
                message: 'Earlier line issue.',
                file: $file,
                line: 12,
                suggestion: 'Fix earlier line.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 120,
                endPos: 127,
                replacement: 'earlier',
            ),
        ];

        $output = renderDryRunOutput(
            rules: [$rule],
            violations: $violations,
            previews: [
                $file => [
                    ['line' => 78, 'column' => 1, 'from' => 'line 78', 'to' => 'line 78 updated'],
                    ['line' => 12, 'column' => 1, 'from' => 'line 12', 'to' => 'line 12 updated'],
                    ['line' => 1, 'column' => 1, 'from' => '', 'to' => 'use App\\Support\\Sorted;'],
                ],
            ],
            byFile: [$file => ['fixed' => 3, 'skipped' => 0]],
        );

        $lineOnePosition = strpos($output, '@@ line 1 @@');
        $lineTwelvePosition = strpos($output, '@@ line 12 @@');
        $lineSeventyEightPosition = strpos($output, '@@ line 78 @@');

        expect($lineOnePosition)->not->toBeFalse()
            ->and($lineTwelvePosition)->not->toBeFalse()
            ->and($lineSeventyEightPosition)->not->toBeFalse()
            ->and($lineOnePosition)->toBeLessThan($lineTwelvePosition)
            ->and($lineTwelvePosition)->toBeLessThan($lineSeventyEightPosition);
    });
});

it('keeps each proposed diff with its own violation when violations are reported out of order', function () {
    withTempDir(function (string $tempDir) {
        $file = $tempDir . '/OutOfOrderViolations.php';
        writeFileWithLines($file, 120);

        $rule = createRule('dry-run-out-of-order');
        $violations = [
            new Violation(
                level: 'warning',
                message: 'Manual fix later in the file.',
                file: $file,
                line: 100,
                suggestion: 'Apply the manual fix.',
                rule: $rule->name(),
                isFixable: false,
            ),
            new Violation(
                level: 'warning',
                message: 'Fixable issue on line 99.',
                file: $file,
                line: 99,
                suggestion: 'Apply the fix on line 99.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 990,
                endPos: 996,
                replacement: 'fixed99',
            ),
            new Violation(
                level: 'warning',
                message: 'Fixable issue on line 98.',
                file: $file,
                line: 98,
                suggestion: 'Apply the fix on line 98.',
                rule: $rule->name(),
                isFixable: true,
                startPos: 980,
                endPos: 986,
                replacement: 'fixed98',
            ),
        ];

        $output = renderDryRunOutput(
            rules: [$rule],
            violations: $violations,
            previews: [
                $file => [
                    ['line' => 99, 'column' => 1, 'from' => 'line 99', 'to' => 'line 99 updated'],
                    ['line' => 98, 'column' => 1, 'from' => 'line 98', 'to' => 'line 98 updated'],
                ],
            ],
            byFile: [$file => ['fixed' => 2, 'skipped' => 1]],
        );

        $line98MessagePosition = strpos($output, 'Line 98: Fixable issue on line 98. (proposed)');
        $line98DiffPosition = strpos($output, '@@ line 98 @@');
        $line99MessagePosition = strpos($output, 'Line 99: Fixable issue on line 99. (proposed)');
        $line99DiffPosition = strpos($output, '@@ line 99 @@');
        $line100MessagePosition = strpos($output, 'Line 100: Manual fix later in the file. (manual fix required)');

        expect(substr_count($output, 'Proposed file changes:'))->toBe(2)
            ->and($line98MessagePosition)->not->toBeFalse()
            ->and($line98DiffPosition)->not->toBeFalse()
            ->and($line99MessagePosition)->not->toBeFalse()
            ->and($line99DiffPosition)->not->toBeFalse()
            ->and($line100MessagePosition)->not->toBeFalse()
            ->and($line98MessagePosition)->toBeLessThan($line98DiffPosition)
            ->and($line98DiffPosition)->toBeLessThan($line99MessagePosition)
            ->and($line99MessagePosition)->toBeLessThan($line99DiffPosition)
            ->and($line99DiffPosition)->toBeLessThan($line100MessagePosition);
    });
});
