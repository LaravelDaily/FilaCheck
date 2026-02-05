<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

class DeprecatedImageColumnSizeRule implements FixableRule
{
    public function name(): string
    {
        return 'deprecated-image-column-size';
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

        if ($node->name->name !== 'size') {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $endPos = $nameNode->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'The `size()` method on ImageColumn is deprecated.',
                file: $context->file,
                line: $node->getLine(),
                suggestion: 'Use `imageSize()` instead of `size()`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'imageSize',
            ),
        ];
    }
}
