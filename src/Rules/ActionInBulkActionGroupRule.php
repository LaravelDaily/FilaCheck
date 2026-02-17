<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\AddsImport;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

class ActionInBulkActionGroupRule implements FixableRule
{
    use AddsImport;
    use CalculatesLineNumbers;

    public function name(): string
    {
        return 'action-in-bulk-action-group';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::BestPractices;
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
        $reported = [];

        $isActionMake = function (Node $n): bool {
            return $n instanceof StaticCall
                && $n->class instanceof Name
                && class_basename($n->class->toString()) === 'Action'
                && $n->name instanceof Identifier
                && $n->name->name === 'make';
        };

        // Check inside toolbarActions() for Action::make() in BulkActionGroup or directly
        $toolbarActionsMethods = $nodeFinder->find($node, function (Node $n) {
            return $n instanceof MethodCall
                && $n->name instanceof Identifier
                && $n->name->name === 'toolbarActions';
        });

        foreach ($toolbarActionsMethods as $toolbarActionsMethod) {
            // Only search within args, not the entire node.
            // The node's `var` property contains earlier chained calls
            // (e.g. ->recordActions([...])->toolbarActions([...])),
            // and searching the full node would cause false positives.
            foreach ($toolbarActionsMethod->getArgs() as $arg) {
                // Check inside BulkActionGroup::make()
                $bulkActionGroups = $nodeFinder->find($arg, function (Node $n) {
                    return $n instanceof StaticCall
                        && $n->class instanceof Name
                        && class_basename($n->class->toString()) === 'BulkActionGroup'
                        && $n->name instanceof Identifier
                        && $n->name->name === 'make';
                });

                foreach ($bulkActionGroups as $bulkActionGroup) {
                    $actionCalls = $nodeFinder->find($bulkActionGroup, $isActionMake);

                    foreach ($actionCalls as $actionCall) {
                        $startPos = $actionCall->class->getStartFilePos();
                        $reported[$startPos] = true;

                        $violations[] = $this->buildActionViolation($actionCall, $context);
                    }
                }

                // Check Action::make() directly inside toolbarActions (not in BulkActionGroup)
                $actionCalls = $nodeFinder->find($arg, $isActionMake);

                foreach ($actionCalls as $actionCall) {
                    $startPos = $actionCall->class->getStartFilePos();

                    if (isset($reported[$startPos])) {
                        continue;
                    }

                    $violations[] = $this->buildActionViolation($actionCall, $context);
                }
            }
        }

        if (! empty($violations)) {
            $importViolation = $this->buildImportViolation(
                'use Filament\Actions\BulkAction;',
                $context,
            );

            if ($importViolation !== null) {
                $violations[] = $importViolation;
            }
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

    private function buildActionViolation(StaticCall $actionCall, Context $context): Violation
    {
        $classNode = $actionCall->class;
        $startPos = $classNode->getStartFilePos();
        $endPos = $classNode->getEndFilePos() + 1;

        return new Violation(
            level: 'error',
            message: '`Action::make()` is used inside `toolbarActions()`. Use `BulkAction::make()` instead.',
            file: $context->file,
            line: $this->getLineFromPosition($context->code, $startPos),
            suggestion: 'Replace `Action::make()` with `BulkAction::make()`.',
            isFixable: true,
            startPos: $startPos,
            endPos: $endPos,
            replacement: 'BulkAction',
        );
    }
}
