<?php

namespace Filacheck\Rules;

class DeprecatedFormAssertsRule extends BaseDeprecatedMethodsRule
{
    /**
     * @var array<string, string>
     */
    protected array $deprecatedMethods = [
        'assertFormSet' => 'assertSchemaStateSet()',
        'assertFormExists' => 'assertSchemaExists()',
        'assertFormFieldHidden' => 'assertSchemaComponentHidden()',
        'assertFormFieldVisible' => 'assertSchemaComponentVisible()',
        'assertFormComponentExists' => 'assertSchemaComponentExists()',
        'assertFormComponentDoesNotExist' => 'assertSchemaComponentDoesNotExist()',
        'mountFormComponentAction' => 'mountAction(TestAction::make(...)->schemaComponent(...))',
        'unmountFormComponentAction' => 'unmountAction()',
        'setFormComponentActionData' => 'fillForm()',
        'assertFormComponentActionDataSet' => 'assertSchemaStateSet()',
        'callFormComponentAction' => 'callAction(TestAction::make(...)->schemaComponent(...), data: [...])',
        'callMountedFormComponentAction' => 'callMountedAction()',
        'assertFormComponentActionExists' => 'assertActionExists(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionDoesNotExist' => 'assertActionDoesNotExist(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionVisible' => 'assertActionVisible(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionHidden' => 'assertActionHidden(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionEnabled' => 'assertActionEnabled(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionDisabled' => 'assertActionDisabled(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionMounted' => 'assertActionMounted(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionNotMounted' => 'assertActionNotMounted(TestAction::make(...)->schemaComponent(...))',
        'assertFormComponentActionHalted' => 'assertActionHalted(TestAction::make(...)->schemaComponent(...))',
        'assertHasFormComponentActionErrors' => 'assertHasFormErrors()',
        'assertHasNoFormComponentActionErrors' => 'assertHasNoFormErrors()',
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
    ];

    public function name(): string
    {
        return 'deprecated-form-asserts';
    }
}
