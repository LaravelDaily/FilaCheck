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
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;

class DeprecatedGetTableQueryRule implements FixableRule, ProvidesAgentFix
{
    use AddsImport;
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

    public function name(): string
    {
        return 'deprecated-get-table-query';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof Class_) {
            return [];
        }

        $getTableQueryMethod = $this->findMethod($node, 'getTableQuery');

        if (! $getTableQueryMethod) {
            return [];
        }

        $line = $this->getLineFromPosition($context->code, $getTableQueryMethod->getStartFilePos());

        $tableMethod = $this->findMethod($node, 'table');

        if (! $tableMethod) {
            return [
                new Violation(
                    level: 'warning',
                    message: 'The `getTableQuery()` method is deprecated.',
                    file: $context->file,
                    line: $line,
                    suggestion: 'Move the query to `->query()` inside the `table(Table $table)` method. See: ' . $this->filamentDocsUrl('components/table'),
                ),
            ];
        }

        if ($this->methodHasQueryCall($tableMethod)) {
            return [
                new Violation(
                    level: 'warning',
                    message: 'The `getTableQuery()` method is deprecated.',
                    file: $context->file,
                    line: $line,
                    suggestion: 'Remove `getTableQuery()` — the `table()` method already has a `->query()` call. See: ' . $this->filamentDocsUrl('components/table'),
                ),
            ];
        }

        $returnExpr = $this->getSimpleReturnExpression($getTableQueryMethod);

        if (! $returnExpr) {
            return [
                new Violation(
                    level: 'warning',
                    message: 'The `getTableQuery()` method is deprecated.',
                    file: $context->file,
                    line: $line,
                    suggestion: 'Move the query logic to `->query()` inside the `table(Table $table)` method. See: ' . $this->filamentDocsUrl(''),
                ),
            ];
        }

        $tableVar = $this->findTableVariable($tableMethod);

        if (! $tableVar) {
            return [
                new Violation(
                    level: 'warning',
                    message: 'The `getTableQuery()` method is deprecated.',
                    file: $context->file,
                    line: $line,
                    suggestion: 'Move the query to `->query()` inside the `table(Table $table)` method. See: ' . $this->filamentDocsUrl('components/table'),
                ),
            ];
        }

        $queryExprText = substr(
            $context->code,
            $returnExpr->getStartFilePos(),
            $returnExpr->getEndFilePos() - $returnExpr->getStartFilePos() + 1
        );

        $indent = $this->getChainIndentation($context->code, $tableMethod);

        $violations = [];

        // Violation 1: Insert ->query() into the table() chain
        $insertPos = $tableVar->getEndFilePos() + 1;
        $violations[] = new Violation(
            level: 'warning',
            message: 'The `getTableQuery()` method is deprecated.',
            file: $context->file,
            line: $line,
            suggestion: 'Move the query to `->query()` inside the `table(Table $table)` method. See: ' . $this->filamentDocsUrl('components/table'),
            isFixable: true,
            startPos: $insertPos,
            endPos: $insertPos,
            replacement: "\n".$indent.'->query(fn (): Builder => '.$queryExprText.')',
        );

        // Violation 2: Add Builder import if missing
        $importViolation = $this->buildImportViolation('use Illuminate\Database\Eloquent\Builder;', $context);
        if ($importViolation) {
            $violations[] = $importViolation;
        }

        // Violation 3: Remove the getTableQuery() method
        [$deleteStart, $deleteEnd] = $this->getCleanDeleteRange($context->code, $getTableQueryMethod);
        $violations[] = new Violation(
            level: 'warning',
            message: 'Removing deprecated `getTableQuery()` method.',
            file: $context->file,
            line: $line,
            suggestion: 'This method has been migrated to `->query()` in the `table()` method. See: ' . $this->filamentDocsUrl('components/table'),
            isFixable: true,
            startPos: $deleteStart,
            endPos: $deleteEnd,
            replacement: '',
            silent: true,
        );

        return $violations;
    }

    public function agentFix(Violation $violation): mixed
    {
        return [
            'instructions' => 'Move the query from the deprecated `getTableQuery()` method into a `->query()` call inside `table(Table $table)`.',
            'next_steps' => [
                'Inside the existing `table(Table $table)` method, chain `->query(fn (): Builder => /* the original return expression */)` directly off `$table`.',
                'Add `use Illuminate\Database\Eloquent\Builder;` at the top of the file if it is not already imported.',
                'Delete the `getTableQuery()` method completely — it is no longer wired up.',
                'If the body of `getTableQuery()` is more than a single return statement, lift the logic into the closure manually rather than relying on the auto-fix.',
            ],
            'docs' => $this->filamentDocsUrl('components/table'),
        ];
    }

    private function findMethod(Class_ $class, string $name): ?ClassMethod
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->name === $name) {
                return $stmt;
            }
        }

        return null;
    }

    private function getSimpleReturnExpression(ClassMethod $method): ?Node\Expr
    {
        if (! $method->stmts || count($method->stmts) !== 1) {
            return null;
        }

        $stmt = $method->stmts[0];

        if (! $stmt instanceof Return_ || ! $stmt->expr) {
            return null;
        }

        return $stmt->expr;
    }

    private function methodHasQueryCall(ClassMethod $method): bool
    {
        $nodeFinder = new NodeFinder;

        $queryCalls = $nodeFinder->find($method, function (Node $n) {
            return $n instanceof MethodCall
                && $n->name instanceof Identifier
                && $n->name->name === 'query';
        });

        return count($queryCalls) > 0;
    }

    private function findTableVariable(ClassMethod $method): ?Variable
    {
        if (! $method->stmts) {
            return null;
        }

        // Find the return statement
        $returnStmt = null;
        foreach ($method->stmts as $stmt) {
            if ($stmt instanceof Return_ && $stmt->expr) {
                $returnStmt = $stmt;
                break;
            }
        }

        if (! $returnStmt) {
            return null;
        }

        // Walk the method chain to find the root $table variable
        $expr = $returnStmt->expr;
        while ($expr instanceof MethodCall) {
            $expr = $expr->var;
        }

        if ($expr instanceof Variable && $expr->name === 'table') {
            return $expr;
        }

        return null;
    }

    private function getChainIndentation(string $code, ClassMethod $method): string
    {
        // Find the first -> in the table method's return to determine indentation
        $methodCode = substr($code, $method->getStartFilePos(), $method->getEndFilePos() - $method->getStartFilePos() + 1);

        if (preg_match('/\n(\s+)->/', $methodCode, $matches)) {
            return $matches[1];
        }

        // Fallback: use 12 spaces (3 levels of 4-space indent)
        return '            ';
    }

    /**
     * @return array{int, int}
     */
    private function getCleanDeleteRange(string $code, ClassMethod $method): array
    {
        $start = $method->getStartFilePos();
        $end = $method->getEndFilePos() + 1;

        // Extend backward past leading whitespace to start of line
        while ($start > 0 && in_array($code[$start - 1], [' ', "\t"])) {
            $start--;
        }

        // Consume preceding newline
        if ($start > 0 && $code[$start - 1] === "\n") {
            $start--;
        }

        // Consume trailing newline
        if ($end < strlen($code) && $code[$end] === "\n") {
            $end++;
        }

        return [$start, $end];
    }
}
