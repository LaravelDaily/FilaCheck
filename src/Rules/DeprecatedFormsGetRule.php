<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\AddsImport;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Rules\Concerns\ResolvesFilamentDocsUrl;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Use_;

class DeprecatedFormsGetRule implements FixableRule, ProvidesAgentFix
{
    use AddsImport;
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

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
                    suggestion: 'Use `Filament\Schemas\Components\Utilities\Get` instead of `Filament\Forms\Get`. See: ' . $this->filamentDocsUrl('forms/overview#injecting-the-state-of-another-field'),
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
                    suggestion: 'Use `Filament\Schemas\Components\Utilities\Get $get` instead of `callable $get`. See: ' . $this->filamentDocsUrl('forms/overview#injecting-the-state-of-another-field'),
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

    public function agentFix(Violation $violation): mixed
    {
        return [
            'instructions' => 'Migrate from the deprecated `Filament\Forms\Get` to `Filament\Schemas\Components\Utilities\Get`.',
            'next_steps' => [
                'Replace the import `use Filament\Forms\Get;` with `use Filament\Schemas\Components\Utilities\Get;`.',
                'Change any closure parameter typed as `callable $get` (when the variable is `$get`) to `Get $get` so state lookups stay type-safe.',
                'If several files import the old namespace, repeat the rename in every file — each is its own violation.',
            ],
            'docs' => $this->filamentDocsUrl('forms/overview#injecting-the-state-of-another-field'),
        ];
    }
}
