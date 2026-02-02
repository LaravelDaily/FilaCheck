<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

class DeprecatedMutateFormDataUsingRule implements FixableRule
{
    public function name(): string
    {
        return 'deprecated-mutate-form-data-using';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof MethodCall) {
            return [];
        }

        if (! $node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'mutateFormDataUsing') {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $endPos = $nameNode->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'The `mutateFormDataUsing()` method is deprecated in Filament v4.',
                file: $context->file,
                line: $node->getLine(),
                suggestion: 'Use `mutateDataUsing()` instead.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'mutateDataUsing',
            ),
        ];
    }
}
