<?php

namespace Filacheck\Rules;

class DeprecatedActionAssertsRule extends BaseDeprecatedMethodsRule
{
    /**
     * @var array<string, string>
     */
    protected array $deprecatedMethods = [
        'setActionData' => 'fillForm()',
        'assertActionDataSet' => 'assertSchemaStateSet()',
        'assertHasActionErrors' => 'assertHasFormErrors()',
        'assertHasNoActionErrors' => 'assertHasNoFormErrors()',
    ];

    public function name(): string
    {
        return 'deprecated-asserts';
    }
}
