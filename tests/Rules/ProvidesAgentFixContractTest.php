<?php

use Filacheck\Rules\ActionInBulkActionGroupRule;
use Filacheck\Rules\DeprecatedActionFormRule;
use Filacheck\Rules\DeprecatedBulkActionsRule;
use Filacheck\Rules\DeprecatedEmptyLabelRule;
use Filacheck\Rules\DeprecatedFilterFormRule;
use Filacheck\Rules\DeprecatedFormsGetRule;
use Filacheck\Rules\DeprecatedFormsSetRule;
use Filacheck\Rules\DeprecatedGetTableQueryRule;
use Filacheck\Rules\DeprecatedImageColumnSizeRule;
use Filacheck\Rules\DeprecatedMutateFormDataUsingRule;
use Filacheck\Rules\DeprecatedPlaceholderRule;
use Filacheck\Rules\DeprecatedReactiveRule;
use Filacheck\Rules\DeprecatedTestMethodsRule;
use Filacheck\Rules\DeprecatedUrlParametersRule;
use Filacheck\Rules\DeprecatedViewPropertyRule;
use Filacheck\Rules\ProvidesAgentFix;
use Filacheck\Rules\WrongTabNamespaceRule;
use Filacheck\Support\Violation;

dataset('agent-fix rules', [
    'ActionInBulkActionGroupRule' => [ActionInBulkActionGroupRule::class],
    'DeprecatedActionFormRule' => [DeprecatedActionFormRule::class],
    'DeprecatedBulkActionsRule' => [DeprecatedBulkActionsRule::class],
    'DeprecatedEmptyLabelRule' => [DeprecatedEmptyLabelRule::class],
    'DeprecatedFilterFormRule' => [DeprecatedFilterFormRule::class],
    'DeprecatedFormsGetRule' => [DeprecatedFormsGetRule::class],
    'DeprecatedFormsSetRule' => [DeprecatedFormsSetRule::class],
    'DeprecatedGetTableQueryRule' => [DeprecatedGetTableQueryRule::class],
    'DeprecatedImageColumnSizeRule' => [DeprecatedImageColumnSizeRule::class],
    'DeprecatedMutateFormDataUsingRule' => [DeprecatedMutateFormDataUsingRule::class],
    'DeprecatedPlaceholderRule' => [DeprecatedPlaceholderRule::class],
    'DeprecatedReactiveRule' => [DeprecatedReactiveRule::class],
    'DeprecatedTestMethodsRule' => [DeprecatedTestMethodsRule::class],
    'DeprecatedUrlParametersRule' => [DeprecatedUrlParametersRule::class],
    'DeprecatedViewPropertyRule' => [DeprecatedViewPropertyRule::class],
    'WrongTabNamespaceRule' => [WrongTabNamespaceRule::class],
]);

it('implements ProvidesAgentFix and returns a JSON-serializable structured fix', function (string $ruleClass) {
    $rule = new $ruleClass;

    expect($rule)->toBeInstanceOf(ProvidesAgentFix::class);

    $violation = new Violation(
        level: 'warning',
        message: 'The `assertFormSet()` method is deprecated.',
        file: 'app/Filament/Resources/UserResource.php',
        line: 42,
        rule: $rule->name(),
    );

    $fix = $rule->agentFix($violation);

    expect($fix)
        ->toBeArray()
        ->toHaveKeys(['instructions', 'next_steps', 'docs']);

    expect($fix['instructions'])->toBeString()->not->toBeEmpty();
    expect($fix['next_steps'])->toBeArray()->not->toBeEmpty();

    foreach ($fix['next_steps'] as $step) {
        expect($step)->toBeString()->not->toBeEmpty();
    }

    expect($fix['docs'])->toBeString()->toStartWith('https://filamentphp.com/');

    expect(json_encode($fix))->toBeString()->not->toBeFalse();
})->with('agent-fix rules');
