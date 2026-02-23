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
use PhpParser\Node\Scalar\String_;

class DeprecatedEmptyLabelRule implements FixableRule
{
    use CalculatesLineNumbers;
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
        if ($this->isTableColumn($node, $context)) {
            return [];
        }

        // Replace the entire method call: ->label('') becomes ->hiddenLabel() or ->iconButton()
        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        // End position includes the closing parenthesis
        $endPos = $node->getEndFilePos() + 1;

        // Use iconButton() for Actions, hiddenLabel() for everything else
        if ($this->isAction($node, $context)) {
            return [
                new Violation(
                    level: 'warning',
                    message: 'Using `label(\'\')` to hide labels is deprecated.',
                    file: $context->file,
                    line: $this->getLineFromPosition($context->code, $startPos),
                    suggestion: 'Use `iconButton()` instead of `label(\'\')` for Actions.',
                    isFixable: true,
                    startPos: $startPos,
                    endPos: $endPos,
                    replacement: 'iconButton()',
                ),
            ];
        }

        return [
            new Violation(
                level: 'warning',
                message: 'Using `label(\'\')` to hide labels is deprecated.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
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
    private function isTableColumn(MethodCall $node, Context $context): bool
    {
        $rootClass = $this->getRootClassName($node, $context);

        if ($rootClass === null) {
            return false;
        }

        // Check if the class name ends with "Column" (e.g., TextColumn, IconColumn)
        $shortName = $this->classBasename($rootClass);

        return str_ends_with($shortName, 'Column');
    }

    /**
     * Check if the method chain originates from an Action class.
     */
    private function isAction(MethodCall $node, Context $context): bool
    {
        $rootClass = $this->getRootClassName($node, $context);

        if ($rootClass === null) {
            return false;
        }

        // Check if the class name ends with "Action" (e.g., Action, EditAction, DeleteAction)
        $shortName = $this->classBasename($rootClass);

        return str_ends_with($shortName, 'Action');
    }

    /**
     * Traverse up the method chain to find the root class name.
     */
    private function getRootClassName(Node $node, Context $context): ?string
    {
        $current = $node;

        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        if ($current instanceof StaticCall && $current->class instanceof Name) {
            return $current->class->toString();
        }

        if ($current instanceof Variable && $current->name === 'this') {
            if (preg_match('/class\s+\w+\s+extends\s+(\w+)/', $context->code, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function classBasename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
