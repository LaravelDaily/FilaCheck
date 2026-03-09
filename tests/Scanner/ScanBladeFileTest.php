<?php

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\BladeRule;
use Filacheck\Scanner\ResourceScanner;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;

function createTestBladeRule(): BladeRule
{
    return new class implements BladeRule
    {
        public function name(): string
        {
            return 'test-blade-rule';
        }

        public function category(): RuleCategory
        {
            return RuleCategory::BestPractices;
        }

        public function check(Node $node, Context $context): array
        {
            return [];
        }

        public function checkBlade(Context $context): array
        {
            if (str_contains($context->code, '@deprecated')) {
                return [
                    new Violation(
                        level: 'warning',
                        message: 'Found @deprecated in blade file.',
                        file: $context->file,
                        line: 1,
                    ),
                ];
            }

            return [];
        }
    };
}

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/filacheck-blade-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    $files = glob($this->tempDir.'/*');
    foreach ($files as $file) {
        unlink($file);
    }
    rmdir($this->tempDir);
});

it('scans a single blade file and finds violations', function () {
    $file = $this->tempDir.'/form.blade.php';
    file_put_contents($file, '<div>@deprecated directive</div>');

    $scanner = new ResourceScanner;
    $scanner->addRule(createTestBladeRule());

    $violations = $scanner->scanBladeFile($file, $this->tempDir);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->message)->toBe('Found @deprecated in blade file.');
    expect($violations[0]->rule)->toBe('test-blade-rule');
});

it('scans a single blade file with no violations', function () {
    $file = $this->tempDir.'/form.blade.php';
    file_put_contents($file, '<div>Clean blade file</div>');

    $scanner = new ResourceScanner;
    $scanner->addRule(createTestBladeRule());

    $violations = $scanner->scanBladeFile($file, $this->tempDir);

    expect($violations)->toHaveCount(0);
});

it('returns empty array when file does not exist', function () {
    $scanner = new ResourceScanner;
    $scanner->addRule(createTestBladeRule());

    $violations = $scanner->scanBladeFile($this->tempDir.'/nonexistent.blade.php', $this->tempDir);

    expect($violations)->toHaveCount(0);
});

it('returns empty array when no blade rules are registered', function () {
    $file = $this->tempDir.'/form.blade.php';
    file_put_contents($file, '<div>@deprecated directive</div>');

    $scanner = new ResourceScanner;
    // No blade rules added

    $violations = $scanner->scanBladeFile($file, $this->tempDir);

    expect($violations)->toHaveCount(0);
});
