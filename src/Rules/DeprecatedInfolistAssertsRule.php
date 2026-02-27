<?php

namespace Filacheck\Rules;

class DeprecatedInfolistAssertsRule extends BaseDeprecatedMethodsRule
{
    /**
     * @var array<string, string>
     */
    protected array $deprecatedMethods = [
        'mountInfolistAction' => 'mountAction(TestAction::make(...)->schemaComponent(...))',
        'unmountInfolistAction' => 'unmountAction()',
        'setInfolistActionData' => 'fillForm()',
        'assertInfolistActionDataSet' => 'assertSchemaStateSet()',
        'callInfolistAction' => 'callAction(TestAction::make(...)->schemaComponent(...), data: [...])',
        'callMountedInfolistAction' => 'callMountedAction()',
        'assertInfolistActionExists' => 'assertActionExists(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionDoesNotExist' => 'assertActionDoesNotExist(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionVisible' => 'assertActionVisible(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionHidden' => 'assertActionHidden(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionEnabled' => 'assertActionEnabled(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionDisabled' => 'assertActionDisabled(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionMounted' => 'assertActionMounted(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->schemaComponent(...))',
        'assertInfolistActionHalted' => 'assertActionHalted(TestAction::make(...)->schemaComponent(...))',
        'assertHasInfolistActionErrors' => 'assertHasFormErrors()',
        'assertHasNoInfolistActionErrors' => 'assertHasNoFormErrors()',
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
        return 'deprecated-infolist-asserts';
    }
}
