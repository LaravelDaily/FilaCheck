<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

class DeprecatedReactiveRule implements FixableRule
{
    public function name(): string
    {
        return 'deprecated-reactive';
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

        if ($node->name->name !== 'reactive') {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $endPos = $nameNode->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'The `reactive()` method is deprecated.',
                file: $context->file,
                line: $node->getLine(),
                suggestion: 'Use `live()` instead of `reactive()`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'live',
            ),
        ];
    }
}
