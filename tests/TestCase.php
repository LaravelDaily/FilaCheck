<?php

namespace Filacheck\Tests;

use Filacheck\Fixer\CodeFixer;
use Filacheck\Rules\Rule;
use Filacheck\Scanner\ResourceScanner;
use Filacheck\Support\Violation;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Scan PHP code with a specific rule and return violations.
     *
     * @return Violation[]
     */
    protected function scanCode(Rule $rule, string $code): array
    {
        $tempDir = sys_get_temp_dir().'/filacheck-test-'.uniqid('', true).'-'.getmypid();
        mkdir($tempDir, 0755, true);

        $tempFile = $tempDir.'/TestResource.php';
        file_put_contents($tempFile, $code);

        $scanner = new ResourceScanner;
        $scanner->addRule($rule);

        $violations = $scanner->scan($tempDir);

        unlink($tempFile);
        rmdir($tempDir);

        return $violations;
    }

    /**
     * Scan PHP code with a specific rule and apply fixes, returning the fixed code.
     */
    protected function scanAndFix(Rule $rule, string $code): string
    {
        $tempDir = sys_get_temp_dir().'/filacheck-test-'.uniqid('', true).'-'.getmypid();
        mkdir($tempDir, 0755, true);

        $tempFile = $tempDir.'/TestResource.php';
        file_put_contents($tempFile, $code);

        $scanner = new ResourceScanner;
        $scanner->addRule($rule);

        $violations = $scanner->scan($tempDir);

        $fixer = new CodeFixer;
        $fixer->fix($violations);

        $fixedCode = file_get_contents($tempFile);

        unlink($tempFile);
        rmdir($tempDir);

        return $fixedCode;
    }

    protected function assertNoViolations(array $violations): void
    {
        $this->assertCount(0, $violations, 'Expected no violations but found: '.print_r($violations, true));
    }

    protected function assertViolationCount(int $expected, array $violations): void
    {
        $this->assertCount($expected, $violations, 'Violation count mismatch. Found: '.print_r($violations, true));
    }

    protected function assertViolationMessage(string $expected, array $violations): void
    {
        $messages = array_map(fn (Violation $v) => $v->message, $violations);
        $this->assertTrue(
            in_array($expected, $messages),
            "Expected violation message '{$expected}' not found. Messages: ".implode(', ', $messages)
        );
    }

    protected function assertViolationContains(string $substring, array $violations): void
    {
        $found = false;
        foreach ($violations as $violation) {
            if (str_contains($violation->message, $substring)) {
                $found = true;
                break;
            }
        }

        $messages = array_map(fn (Violation $v) => $v->message, $violations);
        $this->assertTrue(
            $found,
            "No violation message contains '{$substring}'. Messages: ".implode(', ', $messages)
        );
    }

    protected function assertViolationIsFixable(array $violations): void
    {
        foreach ($violations as $violation) {
            $this->assertTrue(
                $violation->isFixable,
                "Violation '{$violation->message}' is not marked as fixable"
            );
        }
    }
}
