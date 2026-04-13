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
use PhpParser\Node\Identifier;

use function is_string;

class DeprecatedTestMethodsRule implements FixableRule, ExtraPathRule, ProvidesAgentFix
{
    use AddsImport;
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

    /**
     * @var array<string, string|array{0: string, 1: string|array{0: string, 1: string}}>
     */
    protected array $deprecatedMethods = [
        'setActionData' => ['fillForm()', 'fillForm'],
        'assertActionDataSet' => ['assertSchemaStateSet()', 'assertSchemaStateSet'],
        'assertHasActionErrors' => ['assertHasFormErrors()', 'assertHasFormErrors'],
        'assertHasNoActionErrors' => ['assertHasNoFormErrors()', 'assertHasNoFormErrors'],
        'mountTableAction' => ['mountAction(TestAction::make(...)->table())', ['mountAction(TestAction::make(', ')->table())']],
        'unmountTableAction' => ['unmountAction()', 'unmountAction'],
        'setTableActionData' => ['fillForm()', 'fillForm'],
        'assertTableActionDataSet' => ['assertSchemaStateSet()', 'assertSchemaStateSet'],
        'callTableAction' => 'callAction(TestAction::make(...)->table(...), data: [...])',
        'callMountedTableAction' => ['callMountedAction()', 'callMountedAction'],
        'assertTableActionExists' => 'assertActionExists(TestAction::make(...)->table($record))',
        'assertTableActionDoesNotExist' => 'assertActionDoesNotExist(TestAction::make(...)->table($record))',
        'assertTableActionVisible' => 'assertActionVisible(TestAction::make(...)->table($record))',
        'assertTableActionHidden' => 'assertActionHidden(TestAction::make(...)->table($record))',
        'assertTableActionEnabled' => 'assertActionEnabled(TestAction::make(...)->table($record))',
        'assertTableActionDisabled' => 'assertActionDisabled(TestAction::make(...)->table($record))',
        'assertTableActionMounted' => ['assertActionMounted(TestAction::make(...)->table())', ['assertActionMounted(TestAction::make(', ')->table())']],
        'assertTableActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->table(...))',
        'assertTableActionHalted' => ['assertActionHalted(TestAction::make(...)->table())', ['assertActionHalted(TestAction::make(', ')->table())']],
        'assertHasTableActionErrors' => ['assertHasFormErrors()', 'assertHasFormErrors'],
        'assertHasNoTableActionErrors' => ['assertHasNoFormErrors()', 'assertHasNoFormErrors'],
        'mountTableBulkAction' => 'mountAction(TestAction::make(...)->table()->bulk())',
        'setTableBulkActionData' => ['fillForm()', 'fillForm'],
        'assertTableBulkActionDataSet' => ['assertSchemaStateSet()', 'assertSchemaStateSet'],
        'callTableBulkAction' => 'selectTableRecords([...])->callAction(TestAction::make(...)->table()->bulk(), data: [...])',
        'callMountedTableBulkAction' => ['callMountedAction()', 'callMountedAction'],
        'assertTableBulkActionExists' => ['assertActionExists(TestAction::make(...)->table()->bulk())', ['assertActionExists(TestAction::make(', ')->table()->bulk())']],
        'assertTableBulkActionDoesNotExist' => ['assertActionDoesNotExist(TestAction::make(...)->table()->bulk())', ['assertActionDoesNotExist(TestAction::make(', ')->table()->bulk())']],
        'assertTableBulkActionsExistInOrder' => "assertActionListInOrder([...], \$component->instance()->getTable()->getBulkActions(), 'table bulk', BulkAction::class)",
        'assertTableBulkActionVisible' => ['assertActionVisible(TestAction::make(...)->table()->bulk())', ['assertActionVisible(TestAction::make(', ')->table()->bulk())']],
        'assertTableBulkActionHidden' => ['assertActionHidden(TestAction::make(...)->table()->bulk())', ['assertActionHidden(TestAction::make(', ')->table()->bulk())']],
        'assertTableBulkActionEnabled' => ['assertActionEnabled(TestAction::make(...)->table()->bulk())', ['assertActionEnabled(TestAction::make(', ')->table()->bulk())']],
        'assertTableBulkActionDisabled' => ['assertActionDisabled(TestAction::make(...)->table()->bulk())', ['assertActionDisabled(TestAction::make(', ')->table()->bulk())']],
        'assertTableActionHasIcon' => 'assertActionHasIcon(TestAction::make(...)->table(...), ...)',
        'assertTableActionDoesNotHaveIcon' => 'assertActionDoesNotHaveIcon(TestAction::make(...)->table(...), ...)',
        'assertTableActionHasLabel' => 'assertActionHasLabel(TestAction::make(...)->table(...), ...)',
        'assertTableActionDoesNotHaveLabel' => 'assertActionDoesNotHaveLabel(TestAction::make(...)->table(...), ...)',
        'assertTableActionHasColor' => 'assertActionHasColor(TestAction::make(...)->table(...), ...)',
        'assertTableActionDoesNotHaveColor' => 'assertActionDoesNotHaveColor(TestAction::make(...)->table(...), ...)',
        'assertTableBulkActionHasIcon' => 'assertActionHasIcon(TestAction::make(...)->table()->bulk(), ...)',
        'assertTableBulkActionDoesNotHaveIcon' => 'assertActionDoesNotHaveIcon(TestAction::make(...)->table()->bulk(), ...)',
        'assertTableBulkActionHasLabel' => 'assertActionHasLabel(TestAction::make(...)->table()->bulk(), ...)',
        'assertTableBulkActionDoesNotHaveLabel' => 'assertActionDoesNotHaveLabel(TestAction::make(...)->table()->bulk(), ...)',
        'assertTableBulkActionHasColor' => 'assertActionHasColor(TestAction::make(...)->table()->bulk(), ...)',
        'assertTableBulkActionDoesNotHaveColor' => 'assertActionDoesNotHaveColor(TestAction::make(...)->table()->bulk(), ...)',
        'assertTableActionHasUrl' => 'assertActionHasUrl(TestAction::make(...)->table(...), ...)',
        'assertTableActionDoesNotHaveUrl' => 'assertActionDoesNotHaveUrl(TestAction::make(...)->table(...), ...)',
        'assertTableActionShouldOpenUrlInNewTab' => 'assertActionShouldOpenUrlInNewTab(TestAction::make(...)->table(...))',
        'assertTableActionShouldNotOpenUrlInNewTab' => 'assertActionShouldNotOpenUrlInNewTab(TestAction::make(...)->table(...))',
        'assertTableBulkActionMounted' => ['assertActionMounted(TestAction::make(...)->table()->bulk())', ['assertActionMounted(TestAction::make(', ')->table()->bulk())']],
        'assertTableBulkActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionHalted' => ['assertActionHalted(TestAction::make(...)->table()->bulk())', ['assertActionHalted(TestAction::make(', ')->table()->bulk())']],
        'assertHasTableBulkActionErrors' => ['assertHasFormErrors()', 'assertHasFormErrors'],
        'assertHasNoTableBulkActionErrors' => ['assertHasNoFormErrors()', 'assertHasNoFormErrors'],
        'assertFormSet' => ['assertSchemaStateSet()', 'assertSchemaStateSet'],
        'assertFormExists' => ['assertSchemaExists()', 'assertSchemaExists'],
        'assertFormFieldHidden' => ['assertSchemaComponentHidden(..., \'form\')', ['assertSchemaComponentHidden(', ', \'form\')']],
        'assertFormFieldVisible' => ['assertSchemaComponentVisible(..., \'form\')', ['assertSchemaComponentVisible(', ', \'form\')']],
        'assertFormComponentExists' => ['assertSchemaComponentExists(..., \'form\')', ['assertSchemaComponentExists(', ', \'form\')']],
        'assertFormComponentDoesNotExist' => ['assertSchemaComponentDoesNotExist(..., \'form\')', ['assertSchemaComponentDoesNotExist(', ', \'form\')']],
        'mountFormComponentAction' => 'mountAction(TestAction::make(...)->schemaComponent(...))',
        'unmountFormComponentAction' => ['unmountAction()', 'unmountAction'],
        'setFormComponentActionData' => ['fillForm()', 'fillForm'],
        'assertFormComponentActionDataSet' => ['assertSchemaStateSet()', 'assertSchemaStateSet'],
        'callFormComponentAction' => 'callAction(TestAction::make(...)->schemaComponent(...), data: [...])',
        'callMountedFormComponentAction' => ['callMountedAction()', 'callMountedAction'],
        'assertFormComponentActionExists' => 'assertActionExists(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionDoesNotExist' => 'assertActionDoesNotExist(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionVisible' => 'assertActionVisible(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionHidden' => 'assertActionHidden(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionEnabled' => 'assertActionEnabled(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionDisabled' => 'assertActionDisabled(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionMounted' => 'assertActionMounted(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionHalted' => 'assertActionHalted(TestAction::make(...)->schemaComponent(...))',
        'assertHasFormComponentActionErrors' => ['assertHasFormErrors()', 'assertHasFormErrors'],
        'assertHasNoFormComponentActionErrors' => ['assertHasNoFormErrors()', 'assertHasNoFormErrors'],
        'assertFormComponentActionHasIcon' => 'assertActionHasIcon(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionDoesNotHaveIcon' => 'assertActionDoesNotHaveIcon(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionHasLabel' => 'assertActionHasLabel(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionDoesNotHaveLabel' => 'assertActionDoesNotHaveLabel(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionHasColor' => 'assertActionHasColor(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionDoesNotHaveColor' => 'assertActionDoesNotHaveColor(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionHasUrl' => 'assertActionHasUrl(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionDoesNotHaveUrl' => 'assertActionDoesNotHaveUrl(TestAction::make(...)->schemaComponent(...), ...)',
        'assertFormComponentActionShouldOpenUrlInNewTab' => 'assertActionShouldOpenUrlInNewTab(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionShouldNotOpenUrlInNewTab' => 'assertActionShouldNotOpenUrlInNewTab(TestAction::make(...)->schemaComponent(...))',
        'mountInfolistAction' => 'mountAction(TestAction::make(...)->schemaComponent(...))',
        'unmountInfolistAction' => ['unmountAction()', 'unmountAction'],
        'setInfolistActionData' => ['fillForm()', 'fillForm'],
        'assertInfolistActionDataSet' => ['assertSchemaStateSet()', 'assertSchemaStateSet'],
        'callInfolistAction' => 'callAction(TestAction::make(...)->schemaComponent(...), data: [...])',
        'callMountedInfolistAction' => ['callMountedAction()', 'callMountedAction'],
        'assertInfolistActionExists' => 'assertActionExists(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionDoesNotExist' => 'assertActionDoesNotExist(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionVisible' => 'assertActionVisible(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionHidden' => 'assertActionHidden(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionEnabled' => 'assertActionEnabled(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionDisabled' => 'assertActionDisabled(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionMounted' => 'assertActionMounted(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionHalted' => 'assertActionHalted(TestAction::make(...)->schemaComponent(...))',
        'assertHasInfolistActionErrors' => ['assertHasFormErrors()', 'assertHasFormErrors'],
        'assertHasNoInfolistActionErrors' => ['assertHasNoFormErrors()', 'assertHasNoFormErrors'],
        'assertInfolistActionHasIcon' => 'assertActionHasIcon(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionDoesNotHaveIcon' => 'assertActionDoesNotHaveIcon(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionHasLabel' => 'assertActionHasLabel(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionDoesNotHaveLabel' => 'assertActionDoesNotHaveLabel(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionHasColor' => 'assertActionHasColor(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionDoesNotHaveColor' => 'assertActionDoesNotHaveColor(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionHasUrl' => 'assertActionHasUrl(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionDoesNotHaveUrl' => 'assertActionDoesNotHaveUrl(TestAction::make(...)->schemaComponent(...), ...)',
        'assertInfolistActionShouldOpenUrlInNewTab' => 'assertActionShouldOpenUrlInNewTab(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionShouldNotOpenUrlInNewTab' => 'assertActionShouldNotOpenUrlInNewTab(TestAction::make(...)->schemaComponent(...))',
    ];

    public function name(): string
    {
        return 'deprecated-test-methods';
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

        $methodName = $node->name->name;

        if (! array_key_exists($methodName, $this->deprecatedMethods)) {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $deprecatedMethod = $this->deprecatedMethods[$methodName];
        $suggestion = is_string($deprecatedMethod) ? $deprecatedMethod : $deprecatedMethod[0];

        $violation = new Violation(
            level: 'warning',
            message: "The `{$methodName}()` method is deprecated.",
            file: $context->file,
            line: $this->getLineFromPosition($context->code, $startPos),
            suggestion: "Use `{$suggestion}` instead.",
        );

        $fix = $this->getFix($node, $context->code, $deprecatedMethod);

        if ($fix !== null) {
            $violation->isFixable = true;
            $violation->startPos = $fix['startPos'];
            $violation->endPos = $fix['endPos'];
            $violation->replacement = $fix['replacement'];
        }

        $violations = [$violation];

        if ($violations === []) {
            return [];
        }

        foreach ($violations as $violation) {
            if (! $violation->isFixable || ! is_string($violation->replacement) || ! str_contains($violation->replacement, 'TestAction::make(')) {
                continue;
            }

            $importViolation = $this->buildImportViolation(
                'use Filament\Actions\Testing\TestAction;',
                $context,
            );

            if ($importViolation !== null) {
                $violations[] = $importViolation;
            }

            break;
        }

        return $violations;
    }

    /**
     * @param  string|array{0: string, 1: string|array{0: string, 1: string}}  $deprecatedMethod
     * @return array{startPos: int, endPos: int, replacement: string}|null
     */
    protected function getFix(MethodCall $node, string $code, string | array $deprecatedMethod): ?array
    {
        if (is_string($deprecatedMethod)) {
            return null;
        }

        $fix = $deprecatedMethod[1];
        $args = $this->getArgumentsSource($node, $code);

        if ($args === null) {
            return null;
        }

        if (is_string($fix)) {
            if (! $node->name instanceof Identifier) {
                return null;
            }

            return [
                'startPos' => $node->name->getStartFilePos(),
                'endPos' => $node->name->getEndFilePos() + 1,
                'replacement' => $fix,
            ];
        }

        return [
            'startPos' => $node->name->getStartFilePos(),
            'endPos' => $node->getEndFilePos() + 1,
            'replacement' => $fix[0] . $args . $fix[1],
        ];
    }

    protected function getArgumentsSource(MethodCall $node, string $code): ?string
    {
        if (! $node->name instanceof Identifier) {
            return null;
        }

        $methodCallSource = substr(
            $code,
            $node->name->getStartFilePos(),
            $node->getEndFilePos() - $node->name->getStartFilePos() + 1,
        );

        if (! $methodCallSource) {
            return null;
        }

        $openingParenthesisPosition = strpos($methodCallSource, '(');
        $closingParenthesisPosition = strrpos($methodCallSource, ')');

        if (! $openingParenthesisPosition || ! $closingParenthesisPosition || $closingParenthesisPosition < $openingParenthesisPosition) {
            return null;
        }

        return substr(
            $methodCallSource,
            $openingParenthesisPosition + 1,
            $closingParenthesisPosition - $openingParenthesisPosition - 1,
        );
    }

    public function extraScanPaths(): array
    {
        return ['test', 'tests'];
    }

    public function agentFix(Violation $violation): mixed
    {
        $methodName = null;

        if (preg_match('/`([A-Za-z0-9_]+)\(\)`/', $violation->message, $matches)) {
            $methodName = $matches[1];
        }

        $replacement = null;

        if ($methodName !== null && array_key_exists($methodName, $this->deprecatedMethods)) {
            $deprecated = $this->deprecatedMethods[$methodName];
            $replacement = is_string($deprecated) ? $deprecated : $deprecated[0];
        }

        return [
            'instructions' => 'Replace the deprecated Filament v3 testing helper with its v4 equivalent.',
            'deprecated_method' => $methodName,
            'replacement' => $replacement,
            'next_steps' => [
                'Rename the test helper according to the v4 mapping. Most table/form helpers move to `TestAction::make(...)->table()` (or `->bulk()`, `->schemaComponent()`) wrappers.',
                'When the replacement uses `TestAction::make(...)`, add `use Filament\Actions\Testing\TestAction;` to the test file (the rule emits a separate import violation for this when needed).',
                'Helpers like `assertHasActionErrors` collapse to `assertHasFormErrors()` — the argument list is unchanged.',
                'Some replacements (e.g. `callTableAction`, `assertTableActionHasIcon`) are not auto-fixable because they require argument restructuring; rewrite them by hand using the suggestion text.',
            ],
            'docs' => $this->filamentDocsUrl('testing'),
        ];
    }
}
