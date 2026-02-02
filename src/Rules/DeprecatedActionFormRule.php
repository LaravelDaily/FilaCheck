<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class DeprecatedActionFormRule implements FixableRule
{
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

        if (! $this->isActionMakeChain($node)) {
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
                line: $node->getLine(),
                suggestion: 'Use `schema()` instead of `form()`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'schema',
            ),
        ];
    }

    private function isActionMakeChain(MethodCall $node): bool
    {
        $current = $node->var;

        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        if (! $current instanceof StaticCall) {
            return false;
        }

        if (! $current->class instanceof Name) {
            return false;
        }

        $className = $current->class->toString();
        $shortName = class_basename($className);

        return in_array($shortName, self::ACTION_CLASSES);
    }
}
