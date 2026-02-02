<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;

class DeprecatedEmptyLabelRule implements FixableRule
{
    public function name(): string
    {
        return 'deprecated-empty-label';
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

        if ($node->name->name !== 'label') {
            return [];
        }

        if (count($node->args) !== 1) {
            return [];
        }

        $firstArg = $node->args[0]->value;

        if (! $firstArg instanceof String_) {
            return [];
        }

        if ($firstArg->value !== '') {
            return [];
        }

        // Replace the entire method call: ->label('') becomes ->hiddenLabel()
        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        // End position includes the closing parenthesis
        $endPos = $node->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'Using `label(\'\')` to hide labels is deprecated.',
                file: $context->file,
                line: $node->getLine(),
                suggestion: 'Use `hiddenLabel()` instead of `label(\'\')`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'hiddenLabel()',
            ),
        ];
    }
}
