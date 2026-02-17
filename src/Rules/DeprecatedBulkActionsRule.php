<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

class DeprecatedBulkActionsRule implements FixableRule
{
    use CalculatesLineNumbers;

    public function name(): string
    {
        return 'deprecated-bulk-actions';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof ClassMethod) {
            return [];
        }

        if (! $this->hasTableParameter($node)) {
            return [];
        }

        $violations = [];
        $nodeFinder = new NodeFinder;

        $bulkActionsCalls = $nodeFinder->find($node, function (Node $n) {
            return $n instanceof MethodCall
                && $n->name instanceof Identifier
                && $n->name->name === 'bulkActions';
        });

        foreach ($bulkActionsCalls as $call) {
            $nameNode = $call->name;
            $startPos = $nameNode->getStartFilePos();
            $endPos = $nameNode->getEndFilePos() + 1;

            $violations[] = new Violation(
                level: 'warning',
                message: 'The `bulkActions()` method is deprecated.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: 'Use `toolbarActions()` instead of `bulkActions()`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'toolbarActions',
            );
        }

        return $violations;
    }

    private function hasTableParameter(ClassMethod $node): bool
    {
        foreach ($node->params as $param) {
            if ($param->type instanceof Name && class_basename($param->type->toString()) === 'Table') {
                return true;
            }
        }

        return false;
    }
}
