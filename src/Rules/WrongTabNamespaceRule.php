<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\AddsImport;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;

class WrongTabNamespaceRule implements FixableRule
{
    use AddsImport;
    use CalculatesLineNumbers;

    private const CORRECT_NAMESPACE = 'Filament\Schemas\Components\Tabs\Tab';

    private array $filesWithTabImport = [];

    public function name(): string
    {
        return 'wrong-tab-namespace';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::BestPractices;
    }

    public function check(Node $node, Context $context): array
    {
        if ($node instanceof Use_) {
            return $this->checkImport($node, $context);
        }

        if ($node instanceof StaticCall) {
            return $this->checkStaticCall($node, $context);
        }

        return [];
    }

    private function checkImport(Use_ $node, Context $context): array
    {
        $violations = [];

        foreach ($node->uses as $use) {
            $name = $use->name->toString();
            $lastPart = class_basename($name);

            if ($lastPart !== 'Tab') {
                continue;
            }

            if ($name === self::CORRECT_NAMESPACE) {
                $this->filesWithTabImport[$context->file] = true;

                continue;
            }

            $this->filesWithTabImport[$context->file] = true;

            if (str_starts_with($name, 'Filament\\')) {

                $startPos = $use->name->getStartFilePos();
                $endPos = $use->name->getEndFilePos() + 1;

                $violations[] = new Violation(
                    level: 'warning',
                    message: "Wrong namespace `{$name}`. The correct namespace is `" . self::CORRECT_NAMESPACE . '`.',
                    file: $context->file,
                    line: $this->getLineFromPosition($context->code, $startPos),
                    suggestion: 'Use `' . self::CORRECT_NAMESPACE . "` instead of `{$name}`.",
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: self::CORRECT_NAMESPACE,
                );
            }
        }

        return $violations;
    }

    private function checkStaticCall(StaticCall $node, Context $context): array
    {
        if (! $node->class instanceof Name || ! $node->name instanceof Identifier || $node->name->name !== 'make') {
            return [];
        }

        $startPos = $node->class->getStartFilePos();
        $endPos = $node->class->getEndFilePos() + 1;
        $originalClassName = substr($context->code, $startPos, $endPos - $startPos);
        $violations = [];

        if ($originalClassName === 'Tabs\Tab') {
            $violations[] = new Violation(
                level: 'warning',
                message: 'v3-style `Tabs\Tab::make()` usage detected. Use `Tab::make()` with the correct import instead.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: 'Replace `Tabs\Tab::make()` with `Tab::make()` and add `use Filament\Schemas\Components\Tabs\Tab;`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'Tab',
            );

            if (! isset($this->filesWithTabImport[$context->file])) {
                $importViolation = $this->buildImportViolation(
                    'use Filament\Schemas\Components\Tabs\Tab;',
                    $context,
                );

                if ($importViolation !== null) {
                    $violations[] = $importViolation;
                }
            }

            return $violations;
        }

        if ($originalClassName === 'Tab') {
            if (! isset($this->filesWithTabImport[$context->file])) {
                $importViolation = $this->buildImportViolation(
                    'use Filament\Schemas\Components\Tabs\Tab;',
                    $context,
                );

                if ($importViolation !== null) {
                    $violations[] = $importViolation;
                }
            }

            return $violations;
        }

        return [];
    }
}
