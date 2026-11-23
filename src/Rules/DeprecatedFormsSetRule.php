<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\AddsImport;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Rules\Concerns\ResolvesFilamentDocsUrl;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Use_;

class DeprecatedFormsSetRule implements FixableRule, ProvidesAgentFix
{
    use AddsImport;
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

    private const NEW_CLASS = 'Filament\Schemas\Components\Utilities\Set';

    private const OLD_CLASS = 'Filament\Forms\Set';

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

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            return $this->checkClosure($node, $context);
        }

        return [];
    }

    private function checkImport(Use_ $node, Context $context): array
    {
        $violations = [];

        foreach ($node->uses as $use) {
            if ($use->name->toString() === self::OLD_CLASS) {
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
                    replacement: self::NEW_CLASS,
                );
            }
        }

        return $violations;
    }

    private function checkClosure(Closure | ArrowFunction $node, Context $context): array
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
                $line = $this->getLineFromPosition($context->code, $startPos);
                $suggestion = 'Use `Filament\Schemas\Components\Utilities\Set $set` instead of `callable $set`. See: ' . $this->filamentDocsUrl('forms/overview#setting-the-state-of-another-field');

                // The old `Filament\Forms\Set` import is renamed to the new class by this
                // same rule, so it already provides the `Set` short name.
                $compatible = $this->renameableLegacyImport(self::OLD_CLASS, self::NEW_CLASS, $context) !== null
                    ? [self::OLD_CLASS]
                    : [];
                $conflict = $this->conflictingImport(self::NEW_CLASS, $context, $compatible);

                if ($conflict !== null) {
                    $violations[] = new Violation(
                        level: 'warning',
                        message: 'Parameter `$set` should be typed as `Set` instead of `callable`.',
                        file: $context->file,
                        line: $line,
                        suggestion: "Fix manually: the short name `Set` is already imported as `{$conflict}`. " . $suggestion,
                    );

                    continue;
                }

                $violations[] = new Violation(
                    level: 'warning',
                    message: 'Parameter `$set` should be typed as `Set` instead of `callable`.',
                    file: $context->file,
                    line: $line,
                    suggestion: $suggestion,
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: 'Set',
                );

                $importViolation = $this->buildImportViolation(self::NEW_CLASS, $context);

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
