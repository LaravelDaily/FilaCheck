<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\AddsImport;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Use_;

class DeprecatedFormsGetRule implements FixableRule
{
    use AddsImport;
    use CalculatesLineNumbers;

    public function name(): string
    {
        return 'deprecated-forms-get';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if ($node instanceof Use_) {
            return $this->checkImport($node, $context);
        }

        if ($node instanceof Closure) {
            return $this->checkClosure($node, $context);
        }

        return [];
    }

    private function checkImport(Use_ $node, Context $context): array
    {
        $violations = [];

        foreach ($node->uses as $use) {
            if ($use->name->toString() === 'Filament\Forms\Get') {
                $startPos = $use->name->getStartFilePos();
                $endPos = $use->name->getEndFilePos() + 1;

                $violations[] = new Violation(
                    level: 'warning',
                    message: 'The `Filament\Forms\Get` class namespace is deprecated.',
                    file: $context->file,
                    line: $this->getLineFromPosition($context->code, $startPos),
                    suggestion: 'Use `Filament\Schemas\Components\Utilities\Get` instead of `Filament\Forms\Get`.',
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: 'Filament\Schemas\Components\Utilities\Get',
                );
            }
        }

        return $violations;
    }

    private function checkClosure(Closure $node, Context $context): array
    {
        $violations = [];

        foreach ($node->params as $param) {
            if (
                $param->var instanceof Node\Expr\Variable
                && $param->var->name === 'get'
                && $param->type instanceof Identifier
                && $param->type->name === 'callable'
            ) {
                $startPos = $param->type->getStartFilePos();
                $endPos = $param->type->getEndFilePos() + 1;

                $violations[] = new Violation(
                    level: 'warning',
                    message: 'Parameter `$get` should be typed as `Get` instead of `callable`.',
                    file: $context->file,
                    line: $this->getLineFromPosition($context->code, $startPos),
                    suggestion: 'Use `Filament\Schemas\Components\Utilities\Get $get` instead of `callable $get`.',
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: 'Get',
                );

                $importViolation = $this->buildImportViolation(
                    'use Filament\Schemas\Components\Utilities\Get;',
                    $context,
                );

                if ($importViolation !== null) {
                    $violations[] = $importViolation;
                }
            }
        }

        return $violations;
    }
}
