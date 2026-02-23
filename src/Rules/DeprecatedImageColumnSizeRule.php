<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class DeprecatedImageColumnSizeRule implements FixableRule
{
    use CalculatesLineNumbers;
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

        if (! $this->isImageColumnContext($node, $context)) {
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
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: 'Use `imageSize()` instead of `size()`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'imageSize',
            ),
        ];
    }

    private function isImageColumnContext(MethodCall $node, Context $context): bool
    {
        $current = $node->var;

        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        if ($current instanceof StaticCall && $current->class instanceof Name) {
            $parts = explode('\\', $current->class->toString());

            return end($parts) === 'ImageColumn';
        }

        if ($current instanceof Variable && $current->name === 'this') {
            return $this->isInsideImageColumnClass($context);
        }

        return false;
    }

    private function isInsideImageColumnClass(Context $context): bool
    {
        if (! preg_match('/class\s+\w+\s+extends\s+(\w+)/', $context->code, $matches)) {
            return false;
        }

        return $matches[1] === 'ImageColumn';
    }
}
