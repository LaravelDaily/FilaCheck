<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class DeprecatedFilterFormRule implements FixableRule
{
    use CalculatesLineNumbers;
    private const FILTER_CLASSES = [
        'Filter',
        'SelectFilter',
        'TernaryFilter',
        'QueryBuilder',
    ];

    public function name(): string
    {
        return 'deprecated-filter-form';
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

        if (! $this->isFilterMakeChain($node)) {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $endPos = $nameNode->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'The `form()` method on filters is deprecated in Filament 4.',
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

    private function isFilterMakeChain(MethodCall $node): bool
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

        return in_array($shortName, self::FILTER_CLASSES);
    }
}
