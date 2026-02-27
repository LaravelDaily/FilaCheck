<?php

namespace Filacheck\Rules;

class DeprecatedTableAssertsRule extends BaseDeprecatedMethodsRule
{
    /**
     * @var array<string, string>
     */
    protected array $deprecatedMethods = [
        'mountTableAction' => 'mountAction(TestAction::make(...)->table(...))',
        'unmountTableAction' => 'unmountAction()',
        'setTableActionData' => 'fillForm()',
        'assertTableActionDataSet' => 'assertSchemaStateSet()',
        'callTableAction' => 'callAction(TestAction::make(...)->table(...), data: [...])',
        'callMountedTableAction' => 'callMountedAction()',
        'assertTableActionExists' => 'assertActionExists(TestAction::make(...)->table(...))',
        'assertTableActionDoesNotExist' => 'assertActionDoesNotExist(TestAction::make(...)->table(...))',
        'assertTableActionVisible' => 'assertActionVisible(TestAction::make(...)->table())',
        'assertTableActionHidden' => 'assertActionHidden(TestAction::make(...)->table(...))',
        'assertTableActionEnabled' => 'assertActionEnabled(TestAction::make(...)->table(...))',
        'assertTableActionDisabled' => 'assertActionDisabled(TestAction::make(...)->table(...))',
        'assertTableActionMounted' => 'assertActionMounted(TestAction::make(...)->table(...))',
        'assertTableActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->table(...))',
        'assertTableActionHalted' => 'assertActionHalted(TestAction::make(...)->table(...))',
        'assertHasTableActionErrors' => 'assertHasFormErrors()',
        'assertHasNoTableActionErrors' => 'assertHasNoFormErrors()',
        'mountTableBulkAction' => 'mountAction(TestAction::make(...)->table()->bulk())',
        'setTableBulkActionData' => 'fillForm()',
        'assertTableBulkActionDataSet' => 'assertSchemaStateSet()',
        'callTableBulkAction' => 'selectTableRecords([...])->callAction(TestAction::make(...)->table()->bulk(), data: [...])',
        'callMountedTableBulkAction' => 'callMountedAction()',
        'assertTableBulkActionExists' => 'assertActionExists(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionDoesNotExist' => 'assertActionDoesNotExist(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionsExistInOrder' => "assertActionListInOrder([...], \$component->instance()->getTable()->getBulkActions(), 'table bulk', BulkAction::class)",
        'assertTableBulkActionVisible' => 'assertActionVisible(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionHidden' => 'assertActionHidden(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionEnabled' => 'assertActionEnabled(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionDisabled' => 'assertActionDisabled(TestAction::make(...)->table()->bulk())',
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
        'assertTableBulkActionMounted' => 'assertActionMounted(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->table()->bulk())',
        'assertTableBulkActionHalted' => 'assertActionHalted(TestAction::make(...)->table()->bulk())',
        'assertHasTableBulkActionErrors' => 'assertHasFormErrors()',
        'assertHasNoTableBulkActionErrors' => 'assertHasNoFormErrors()',
    ];

    public function name(): string
    {
        return 'deprecated-table-asserts';
    }
}
