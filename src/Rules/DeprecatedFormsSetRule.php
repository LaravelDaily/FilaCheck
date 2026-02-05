<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;

class DeprecatedFormsSetRule implements FixableRule
{
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
        if (! $node instanceof Use_) {
            return [];
        }

        $violations = [];

        foreach ($node->uses as $use) {
            if ($use->name->toString() === 'Filament\Forms\Set') {
                $startPos = $use->name->getStartFilePos();
                $endPos = $use->name->getEndFilePos() + 1;

                $violations[] = new Violation(
                    level: 'warning',
                    message: 'The `Filament\Forms\Set` class namespace is deprecated.',
                    file: $context->file,
                    line: $node->getLine(),
                    suggestion: 'Use `Filament\Schemas\Components\Utilities\Set` instead of `Filament\Forms\Set`.',
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: 'Filament\Schemas\Components\Utilities\Set',
                );
            }
        }

        return $violations;
    }
}
