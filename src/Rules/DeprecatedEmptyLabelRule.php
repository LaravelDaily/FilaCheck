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

        // Skip Table Columns - they don't have hiddenLabel() method
        if ($this->isTableColumn($node)) {
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

    /**
     * Check if the method chain originates from a Table Column class.
     */
    private function isTableColumn(MethodCall $node): bool
    {
        $rootClass = $this->getRootClassName($node);

        if ($rootClass === null) {
            return false;
        }

        // Check if the class name ends with "Column" (e.g., TextColumn, IconColumn)
        // or is in the Filament\Tables\Columns namespace
        $shortName = $this->classBasename($rootClass);

        return str_ends_with($shortName, 'Column');
    }

    /**
     * Traverse up the method chain to find the root static call class name.
     */
    private function getRootClassName(Node $node): ?string
    {
        $current = $node;

        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        if ($current instanceof StaticCall && $current->class instanceof Name) {
            return $current->class->toString();
        }

        return null;
    }

    private function classBasename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
