<?php

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\DeprecatedReactiveRule;
use Filacheck\Rules\ExtraPathRule;
use Filacheck\Scanner\ResourceScanner;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;

function createExtraPathRule(array $paths): ExtraPathRule
{
    return new class($paths) implements ExtraPathRule
    {
        public function __construct(private array $paths) {}

        public function name(): string
        {
            return 'test-extra-path-rule';
        }

        public function category(): RuleCategory
        {
            return RuleCategory::BestPractices;
        }

        public function check(Node $node, Context $context): array
        {
            if (! $node instanceof Node\Expr\MethodCall) {
                return [];
            }

            if (! $node->name instanceof Node\Identifier || $node->name->name !== 'reactive') {
                return [];
            }

            return [
                new Violation(
                    level: 'warning',
                    message: 'Found reactive() in extra path scan.',
                    file: $context->file,
                    line: $node->getStartLine(),
                ),
            ];
        }

        public function extraScanPaths(): array
        {
            return $this->paths;
        }
    };
}

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/filacheck-extra-path-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($this->tempDir);
});

it('filters ExtraPathRule instances from getRules', function () {
    $scanner = new ResourceScanner;
    $scanner->addRule(new DeprecatedReactiveRule);
    $scanner->addRule(createExtraPathRule(['app/Models']));

    $extraPathRules = array_filter($scanner->getRules(), fn ($rule) => $rule instanceof ExtraPathRule);

    expect($extraPathRules)->toHaveCount(1);
    expect(array_values($extraPathRules)[0]->extraScanPaths())->toBe(['app/Models']);
});

it('deduplicates extra scan paths from multiple rules', function () {
    $scanner = new ResourceScanner;
    $scanner->addRule(createExtraPathRule(['app/Models', 'app/Enums']));
    $scanner->addRule(createExtraPathRule(['app/Models']));

    $extraPathRules = array_filter($scanner->getRules(), fn ($rule) => $rule instanceof ExtraPathRule);

    $extraPaths = [];
    foreach ($extraPathRules as $rule) {
        foreach ($rule->extraScanPaths() as $extraPath) {
            $extraPaths[$extraPath] = true;
        }
    }

    expect(array_keys($extraPaths))->toBe(['app/Models', 'app/Enums']);
});

it('scans extra path directory and finds violations', function () {
    $filamentDir = $this->tempDir.'/Filament';
    $modelsDir = $this->tempDir.'/Models';
    mkdir($filamentDir, 0755, true);
    mkdir($modelsDir, 0755, true);

    file_put_contents($filamentDir.'/UserResource.php', '<?php $input->live();');
    file_put_contents($modelsDir.'/Order.php', '<?php $input->reactive();');

    $scanner = new ResourceScanner;
    $scanner->addRule(createExtraPathRule(['Models']));

    // Main scan on Filament dir finds no violations (live() is fine)
    $violations = $scanner->scan($filamentDir, $this->tempDir);
    expect($violations)->toHaveCount(0);

    // Scanning extra path finds the violation
    $extraViolations = $scanner->scan($modelsDir, $this->tempDir);
    expect($extraViolations)->toHaveCount(1);
    expect($extraViolations[0]->file)->toBe('Models/Order.php');
});

it('applies only the requesting rules when scan() is given a rule subset', function () {
    $testsDir = $this->tempDir.'/tests';
    mkdir($testsDir, 0755, true);

    file_put_contents($testsDir.'/SomeTest.php', '<?php $input->reactive();');

    $scanner = new ResourceScanner;
    $reactiveRule = new DeprecatedReactiveRule;
    $extraPathRule = createExtraPathRule(['tests']);

    $scanner->addRule($reactiveRule);
    $scanner->addRule($extraPathRule);

    // With no rule subset, every rule applies — both rules match reactive().
    $allRulesViolations = $scanner->scan($testsDir, $this->tempDir);
    $ruleNames = array_map(fn ($v) => $v->rule, $allRulesViolations);
    expect($ruleNames)->toContain('deprecated-reactive');
    expect($ruleNames)->toContain('test-extra-path-rule');

    // Scoped to the extra-path rule only: deprecated-reactive does not fire.
    $scopedViolations = $scanner->scan($testsDir, $this->tempDir, [$extraPathRule]);
    $scopedRuleNames = array_map(fn ($v) => $v->rule, $scopedViolations);
    expect($scopedRuleNames)->not->toContain('deprecated-reactive');
    expect($scopedRuleNames)->toContain('test-extra-path-rule');
});

it('still runs the owning rule when scan() is scoped to it', function () {
    $testsDir = $this->tempDir.'/tests';
    mkdir($testsDir, 0755, true);

    file_put_contents($testsDir.'/SomeTest.php', '<?php $input->reactive();');

    $scanner = new ResourceScanner;
    $extraPathRule = createExtraPathRule(['tests']);
    $scanner->addRule(new DeprecatedReactiveRule);
    $scanner->addRule($extraPathRule);

    $violations = $scanner->scan($testsDir, $this->tempDir, [$extraPathRule]);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->rule)->toBe('test-extra-path-rule');
});

it('skips extra path when already covered by main scan path', function () {
    $mainPath = $this->tempDir.'/app';
    $extraPath = $this->tempDir.'/app/Models';
    mkdir($extraPath, 0755, true);

    file_put_contents($extraPath.'/Order.php', '<?php $input->reactive();');

    // Simulate CLI overlap check: extra path is subdirectory of main path
    $shouldSkip = str_starts_with($extraPath, $mainPath) || str_starts_with($mainPath, $extraPath);
    expect($shouldSkip)->toBeTrue();

    // When not overlapping, scanning should work
    $separatePath = $this->tempDir.'/models';
    mkdir($separatePath, 0755, true);
    file_put_contents($separatePath.'/Order.php', '<?php $input->reactive();');

    $shouldSkipSeparate = str_starts_with($separatePath, $mainPath) || str_starts_with($mainPath, $separatePath);
    expect($shouldSkipSeparate)->toBeFalse();
});
