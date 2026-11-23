<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\AddsImport;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Rules\Concerns\ResolvesFilamentDocsUrl;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;

class ActionInBulkActionGroupRule implements FixableRule, ProvidesAgentFix
{
    use AddsImport;
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

    private const BULK_ACTION_CLASS = 'Filament\Actions\BulkAction';

    private const LEGACY_BULK_ACTION_CLASS = 'Filament\Tables\Actions\BulkAction';

    /** @var array<string, true> */
    private array $legacyImportRenamedForFiles = [];

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

        $isActionMake = function (Node $n): bool {
            return $n instanceof StaticCall
                && $n->class instanceof Name
                && class_basename($n->class->toString()) === 'Action'
                && $n->name instanceof Identifier
                && $n->name->name === 'make';
        };

        // Check inside toolbarActions() for Action::make() in BulkActionGroup.
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
                        $violations[] = $this->buildActionViolation($actionCall, $context);
                    }
                }
            }
        }

        if (empty($violations)) {
            return [];
        }

        // Only a plain `use` of the legacy class under the `BulkAction` name can be
        // rewritten in place; anything else imported as `BulkAction` is a conflict.
        $legacyImport = $this->renameableLegacyImport(self::LEGACY_BULK_ACTION_CLASS, self::BULK_ACTION_CLASS, $context);
        $compatible = $legacyImport !== null ? [self::LEGACY_BULK_ACTION_CLASS] : [];
        $conflict = $this->conflictingImport(self::BULK_ACTION_CLASS, $context, $compatible);

        if ($conflict !== null) {
            foreach ($violations as $violation) {
                $violation->isFixable = false;
                $violation->startPos = null;
                $violation->endPos = null;
                $violation->replacement = null;
                $violation->suggestion = "Fix manually: the short name `BulkAction` is already imported as `{$conflict}`. " . $violation->suggestion;
            }

            return $violations;
        }

        if ($legacyImport !== null) {
            // The v3 `Filament\Tables\Actions\BulkAction` import no longer exists in
            // v4+, so rewrite it in place instead of adding a second `BulkAction`.
            if (! isset($this->legacyImportRenamedForFiles[$context->file])) {
                $this->legacyImportRenamedForFiles[$context->file] = true;

                $violations[] = new Violation(
                    level: 'warning',
                    message: 'Replacing legacy import `' . self::LEGACY_BULK_ACTION_CLASS . '`.',
                    file: $context->file,
                    line: $this->getLineFromPosition($context->code, $legacyImport['startPos']),
                    isFixable: true,
                    startPos: $legacyImport['startPos'],
                    endPos: $legacyImport['endPos'],
                    replacement: self::BULK_ACTION_CLASS,
                    silent: true,
                );
            }

            return $violations;
        }

        $importViolation = $this->buildImportViolation(self::BULK_ACTION_CLASS, $context);

        if ($importViolation !== null) {
            $violations[] = $importViolation;
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

    public function agentFix(Violation $violation): mixed
    {
        return [
            'instructions' => 'Replace `Action::make()` with `BulkAction::make()` whenever it appears inside `BulkActionGroup::make()`.',
            'next_steps' => [
                'Rename the `Action::make()` static call to `BulkAction::make()` — the rest of the chain stays the same.',
                'Make sure the file imports `Filament\Actions\BulkAction`: rewrite a leftover v3 `use Filament\Tables\Actions\BulkAction;` to it, or add `use Filament\Actions\BulkAction;` if `BulkAction` is not imported yet.',
                'Do not rewrite `Action::make()` calls outside `BulkActionGroup::make()`, including actions placed directly in `toolbarActions()`.',
            ],
            'docs' => $this->filamentDocsUrl('tables/actions#bulk-actions'),
        ];
    }

    private function buildActionViolation(StaticCall $actionCall, Context $context): Violation
    {
        $classNode = $actionCall->class;
        $startPos = $classNode->getStartFilePos();
        $endPos = $classNode->getEndFilePos() + 1;

        return new Violation(
            level: 'error',
            message: '`Action::make()` is used inside `BulkActionGroup::make()`. Use `BulkAction::make()` instead.',
            file: $context->file,
            line: $this->getLineFromPosition($context->code, $startPos),
            suggestion: 'Replace `Action::make()` with `BulkAction::make()`. See: ' . $this->filamentDocsUrl('tables/actions#bulk-actions'),
            isFixable: true,
            startPos: $startPos,
            endPos: $endPos,
            replacement: 'BulkAction',
        );
    }
}
