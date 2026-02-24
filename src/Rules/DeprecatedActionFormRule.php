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

class DeprecatedActionFormRule implements FixableRule
{
    use CalculatesLineNumbers;
    private const ACTION_CLASSES = [
        'Action',
        'EditAction',
        'DeleteAction',
        'CreateAction',
        'ViewAction',
        'ReplicateAction',
        'RestoreAction',
        'ForceDeleteAction',
        'BulkAction',
        'DeleteBulkAction',
        'RestoreBulkAction',
        'ForceDeleteBulkAction',
    ];

    public function name(): string
    {
        return 'deprecated-action-form';
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

        if ($node->name->name !== 'form') {
            return [];
        }

        if (! $this->isActionContext($node, $context)) {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $endPos = $nameNode->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'The `form()` method on actions is deprecated in Filament 4.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: 'Use `schema()` instead of `form()`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'schema',
            ),
        ];
    }

    private function isActionContext(MethodCall $node, Context $context): bool
    {
        $current = $node->var;

        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        if ($current instanceof StaticCall) {
            if (! $current->class instanceof Name) {
                return false;
            }

            $className = $current->class->toString();
            $shortName = class_basename($className);

            return in_array($shortName, self::ACTION_CLASSES);
        }

        if ($current instanceof Variable && $current->name === 'this') {
            return $this->isInsideActionClass($context);
        }

        return false;
    }

    private function isInsideActionClass(Context $context): bool
    {
        if (! preg_match('/class\s+\w+\s+extends\s+(\w+)/', $context->code, $matches)) {
            return false;
        }

        return in_array($matches[1], self::ACTION_CLASSES);
    }
}
