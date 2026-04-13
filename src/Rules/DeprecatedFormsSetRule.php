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

class DeprecatedFormsSetRule implements FixableRule, ProvidesAgentFix
{
    use AddsImport;
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

    public function name(): string
    {
        return 'deprecated-forms-set';
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
            if ($use->name->toString() === 'Filament\Forms\Set') {
                $startPos = $use->name->getStartFilePos();
                $endPos = $use->name->getEndFilePos() + 1;

                $violations[] = new Violation(
                    level: 'warning',
                    message: 'The `Filament\Forms\Set` class namespace is deprecated.',
                    file: $context->file,
                    line: $this->getLineFromPosition($context->code, $startPos),
                    suggestion: 'Use `Filament\Schemas\Components\Utilities\Set` instead of `Filament\Forms\Set`. See: ' . $this->filamentDocsUrl('forms/overview#setting-the-state-of-another-field'),
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: 'Filament\Schemas\Components\Utilities\Set',
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
                && $param->var->name === 'set'
                && $param->type instanceof Identifier
                && $param->type->name === 'callable'
            ) {
                $startPos = $param->type->getStartFilePos();
                $endPos = $param->type->getEndFilePos() + 1;

                $violations[] = new Violation(
                    level: 'warning',
                    message: 'Parameter `$set` should be typed as `Set` instead of `callable`.',
                    file: $context->file,
                    line: $this->getLineFromPosition($context->code, $startPos),
                    suggestion: 'Use `Filament\Schemas\Components\Utilities\Set $set` instead of `callable $set`. See: ' . $this->filamentDocsUrl('forms/overview#setting-the-state-of-another-field'),
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: 'Set',
                );

                $importViolation = $this->buildImportViolation(
                    'use Filament\Schemas\Components\Utilities\Set;',
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
            'instructions' => 'Migrate from the deprecated `Filament\Forms\Set` to `Filament\Schemas\Components\Utilities\Set`.',
            'next_steps' => [
                'Replace the import `use Filament\Forms\Set;` with `use Filament\Schemas\Components\Utilities\Set;`.',
                'Change any closure parameter typed as `callable $set` (when the variable is `$set`) to `Set $set` so state mutations stay type-safe.',
                'If several files import the old namespace, repeat the rename in every file — each is its own violation.',
            ],
            'docs' => $this->filamentDocsUrl('forms/overview#setting-the-state-of-another-field'),
        ];
    }
}
