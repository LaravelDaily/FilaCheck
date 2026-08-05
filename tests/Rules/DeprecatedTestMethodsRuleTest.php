<?php

use Filacheck\Rules\DeprecatedTestMethodsRule;

dataset('fixableDeprecatedTestMethods', [
    'setActionData' => [
        '->setActionData([\'title\' => \'New title\'])',
        'Use `fillForm()` instead.',
        '->fillForm([\'title\' => \'New title\'])',
        false,
    ],
    'assertActionDataSet' => [
        '->assertActionDataSet([\'title\' => \'New title\'])',
        'Use `assertSchemaStateSet()` instead.',
        '->assertSchemaStateSet([\'title\' => \'New title\'])',
        false,
    ],
    'assertHasActionErrors' => [
        '->assertHasActionErrors([\'title\'])',
        'Use `assertHasFormErrors()` instead.',
        '->assertHasFormErrors([\'title\'])',
        false,
    ],
    'assertHasNoActionErrors' => [
        '->assertHasNoActionErrors()',
        'Use `assertHasNoFormErrors()` instead.',
        '->assertHasNoFormErrors()',
        false,
    ],
    'mountTableAction' => [
        '->mountTableAction(\'header_with_form\')',
        'Use `mountAction(TestAction::make(...)->table())` instead.',
        '->mountAction(TestAction::make(\'header_with_form\')->table())',
        true,
    ],
    'unmountTableAction' => [
        '->unmountTableAction()',
        'Use `unmountAction()` instead.',
        '->unmountAction()',
        false,
    ],
    'setTableActionData' => [
        '->setTableActionData([\'note\' => \'ready\'])',
        'Use `fillForm()` instead.',
        '->fillForm([\'note\' => \'ready\'])',
        false,
    ],
    'assertTableActionDataSet' => [
        '->assertTableActionDataSet([\'note\' => \'ready\'])',
        'Use `assertSchemaStateSet()` instead.',
        '->assertSchemaStateSet([\'note\' => \'ready\'])',
        false,
    ],
    'callMountedTableAction' => [
        '->callMountedTableAction()',
        'Use `callMountedAction()` instead.',
        '->callMountedAction()',
        false,
    ],
    'assertTableActionMounted' => [
        '->assertTableActionMounted(\'header_with_form\')',
        'Use `assertActionMounted(TestAction::make(...)->table())` instead.',
        '->assertActionMounted(TestAction::make(\'header_with_form\')->table())',
        true,
    ],
    'assertTableActionHalted' => [
        '->assertTableActionHalted(\'header_halted_action\')',
        'Use `assertActionHalted(TestAction::make(...)->table())` instead.',
        '->assertActionHalted(TestAction::make(\'header_halted_action\')->table())',
        true,
    ],
    'assertHasTableActionErrors' => [
        '->assertHasTableActionErrors([\'note\'])',
        'Use `assertHasFormErrors()` instead.',
        '->assertHasFormErrors([\'note\'])',
        false,
    ],
    'assertHasNoTableActionErrors' => [
        '->assertHasNoTableActionErrors()',
        'Use `assertHasNoFormErrors()` instead.',
        '->assertHasNoFormErrors()',
        false,
    ],
    'setTableBulkActionData' => [
        '->setTableBulkActionData([\'note\' => \'ready\'])',
        'Use `fillForm()` instead.',
        '->fillForm([\'note\' => \'ready\'])',
        false,
    ],
    'assertTableBulkActionDataSet' => [
        '->assertTableBulkActionDataSet([\'note\' => \'ready\'])',
        'Use `assertSchemaStateSet()` instead.',
        '->assertSchemaStateSet([\'note\' => \'ready\'])',
        false,
    ],
    'callMountedTableBulkAction' => [
        '->callMountedTableBulkAction()',
        'Use `callMountedAction()` instead.',
        '->callMountedAction()',
        false,
    ],
    'assertTableBulkActionExists' => [
        '->assertTableBulkActionExists(\'bulk_with_form\')',
        'Use `assertActionExists(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionExists(TestAction::make(\'bulk_with_form\')->table()->bulk())',
        true,
    ],
    'assertTableBulkActionDoesNotExist' => [
        '->assertTableBulkActionDoesNotExist(\'missing-action\')',
        'Use `assertActionDoesNotExist(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionDoesNotExist(TestAction::make(\'missing-action\')->table()->bulk())',
        true,
    ],
    'assertTableBulkActionVisible' => [
        '->assertTableBulkActionVisible(\'bulk_with_form\')',
        'Use `assertActionVisible(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionVisible(TestAction::make(\'bulk_with_form\')->table()->bulk())',
        true,
    ],
    'assertTableBulkActionHidden' => [
        '->assertTableBulkActionHidden(\'bulk_hidden_action\')',
        'Use `assertActionHidden(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionHidden(TestAction::make(\'bulk_hidden_action\')->table()->bulk())',
        true,
    ],
    'assertTableBulkActionEnabled' => [
        '->assertTableBulkActionEnabled(\'bulk_with_form\')',
        'Use `assertActionEnabled(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionEnabled(TestAction::make(\'bulk_with_form\')->table()->bulk())',
        true,
    ],
    'assertTableBulkActionDisabled' => [
        '->assertTableBulkActionDisabled(\'bulk_disabled_action\')',
        'Use `assertActionDisabled(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionDisabled(TestAction::make(\'bulk_disabled_action\')->table()->bulk())',
        true,
    ],
    'assertTableBulkActionMounted' => [
        '->assertTableBulkActionMounted(\'bulk_with_form\')',
        'Use `assertActionMounted(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionMounted(TestAction::make(\'bulk_with_form\')->table()->bulk())',
        true,
    ],
    'assertTableBulkActionHalted' => [
        '->assertTableBulkActionHalted(\'bulk_halted_action\')',
        'Use `assertActionHalted(TestAction::make(...)->table()->bulk())` instead.',
        '->assertActionHalted(TestAction::make(\'bulk_halted_action\')->table()->bulk())',
        true,
    ],
    'assertHasTableBulkActionErrors' => [
        '->assertHasTableBulkActionErrors([\'note\'])',
        'Use `assertHasFormErrors()` instead.',
        '->assertHasFormErrors([\'note\'])',
        false,
    ],
    'assertHasNoTableBulkActionErrors' => [
        '->assertHasNoTableBulkActionErrors()',
        'Use `assertHasNoFormErrors()` instead.',
        '->assertHasNoFormErrors()',
        false,
    ],
    'assertFormSet' => [
        '->assertFormSet([\'title\' => \'Draft\'])',
        'Use `assertSchemaStateSet()` instead.',
        '->assertSchemaStateSet([\'title\' => \'Draft\'])',
        false,
    ],
    'assertFormExists' => [
        '->assertFormExists(\'form\')',
        'Use `assertSchemaExists()` instead.',
        '->assertSchemaExists(\'form\')',
        false,
    ],
    'assertFormFieldVisible' => [
        '->assertFormFieldVisible(\'title\')',
        'Use `assertSchemaComponentVisible(..., \'form\')` instead.',
        '->assertSchemaComponentVisible(\'title\', \'form\')',
        false,
    ],
    'assertFormFieldHidden' => [
        '->assertFormFieldHidden(\'hidden_title\')',
        'Use `assertSchemaComponentHidden(..., \'form\')` instead.',
        '->assertSchemaComponentHidden(\'hidden_title\', \'form\')',
        false,
    ],
    'assertFormComponentExists' => [
        '->assertFormComponentExists(\'title\')',
        'Use `assertSchemaComponentExists(..., \'form\')` instead.',
        '->assertSchemaComponentExists(\'title\', \'form\')',
        false,
    ],
    'assertFormComponentDoesNotExist' => [
        '->assertFormComponentDoesNotExist(\'missing_field\')',
        'Use `assertSchemaComponentDoesNotExist(..., \'form\')` instead.',
        '->assertSchemaComponentDoesNotExist(\'missing_field\', \'form\')',
        false,
    ],
    'unmountFormComponentAction' => [
        '->unmountFormComponentAction()',
        'Use `unmountAction()` instead.',
        '->unmountAction()',
        false,
    ],
    'setFormComponentActionData' => [
        '->setFormComponentActionData([\'note\' => \'ready\'])',
        'Use `fillForm()` instead.',
        '->fillForm([\'note\' => \'ready\'])',
        false,
    ],
    'assertFormComponentActionDataSet' => [
        '->assertFormComponentActionDataSet([\'note\' => \'ready\'])',
        'Use `assertSchemaStateSet()` instead.',
        '->assertSchemaStateSet([\'note\' => \'ready\'])',
        false,
    ],
    'callMountedFormComponentAction' => [
        '->callMountedFormComponentAction()',
        'Use `callMountedAction()` instead.',
        '->callMountedAction()',
        false,
    ],
    'assertHasFormComponentActionErrors' => [
        '->assertHasFormComponentActionErrors([\'note\'])',
        'Use `assertHasFormErrors()` instead.',
        '->assertHasFormErrors([\'note\'])',
        false,
    ],
    'assertHasNoFormComponentActionErrors' => [
        '->assertHasNoFormComponentActionErrors()',
        'Use `assertHasNoFormErrors()` instead.',
        '->assertHasNoFormErrors()',
        false,
    ],
    'unmountInfolistAction' => [
        '->unmountInfolistAction()',
        'Use `unmountAction()` instead.',
        '->unmountAction()',
        false,
    ],
    'setInfolistActionData' => [
        '->setInfolistActionData([\'note\' => \'ready\'])',
        'Use `fillForm()` instead.',
        '->fillForm([\'note\' => \'ready\'])',
        false,
    ],
    'assertInfolistActionDataSet' => [
        '->assertInfolistActionDataSet([\'note\' => \'ready\'])',
        'Use `assertSchemaStateSet()` instead.',
        '->assertSchemaStateSet([\'note\' => \'ready\'])',
        false,
    ],
    'callMountedInfolistAction' => [
        '->callMountedInfolistAction()',
        'Use `callMountedAction()` instead.',
        '->callMountedAction()',
        false,
    ],
    'assertHasInfolistActionErrors' => [
        '->assertHasInfolistActionErrors([\'note\'])',
        'Use `assertHasFormErrors()` instead.',
        '->assertHasFormErrors([\'note\'])',
        false,
    ],
    'assertHasNoInfolistActionErrors' => [
        '->assertHasNoInfolistActionErrors()',
        'Use `assertHasNoFormErrors()` instead.',
        '->assertHasNoFormErrors()',
        false,
    ],
    'mountTableAction with a record' => [
        '->mountTableAction(\'edit\', $record)',
        'Use `mountAction(TestAction::make(...)->table())` instead.',
        '->mountAction(TestAction::make(\'edit\')->table($record))',
        true,
    ],
    'assertTableActionMounted with a record' => [
        '->assertTableActionMounted(\'edit\', $record)',
        'Use `assertActionMounted(TestAction::make(...)->table())` instead.',
        '->assertActionMounted(TestAction::make(\'edit\')->table($record))',
        true,
    ],
    'assertTableActionHalted with a record' => [
        '->assertTableActionHalted(\'edit\', $record)',
        'Use `assertActionHalted(TestAction::make(...)->table())` instead.',
        '->assertActionHalted(TestAction::make(\'edit\')->table($record))',
        true,
    ],
    'mountTableAction with a record expression containing a comma' => [
        '->mountTableAction(\'edit\', $posts->firstWhere(\'slug\', \'hello\'))',
        'Use `mountAction(TestAction::make(...)->table())` instead.',
        '->mountAction(TestAction::make(\'edit\')->table($posts->firstWhere(\'slug\', \'hello\')))',
        true,
    ],
]);

dataset('nonFixableDeprecatedTestMethods', [
    'assertTableActionExists' => [
        '->assertTableActionExists(\'with_form\', null, $record)',
        'Use `assertActionExists(TestAction::make(...)->table($record))` instead.',
    ],
    'assertTableActionDoesNotExist' => [
        '->assertTableActionDoesNotExist(\'missing-action\', null, $record)',
        'Use `assertActionDoesNotExist(TestAction::make(...)->table($record))` instead.',
    ],
    'assertTableActionVisible' => [
        '->assertTableActionVisible(\'with_form\', $record)',
        'Use `assertActionVisible(TestAction::make(...)->table($record))` instead.',
    ],
    'callTableBulkAction' => [
        '->callTableBulkAction(\'bulk_with_form\', $records, [\'note\' => null])',
        'Use `selectTableRecords([...])->callAction(TestAction::make(...)->table()->bulk(), data: [...])` instead.',
    ],
    'mountFormComponentAction' => [
        '->mountFormComponentAction(\'action_with_form\', \'with_form\')',
        'Use `mountAction(TestAction::make(...)->schemaComponent(...))` instead.',
    ],
    'assertInfolistActionVisible' => [
        '->assertInfolistActionVisible(\'action_with_form\', \'with_form\')',
        'Use `assertActionVisible(TestAction::make(...)->schemaComponent(...))` instead.',
    ],
]);

it('detects multiple deprecated test methods in one chain', function () {
    $code = <<<'PHP'
<?php

livewire(EditPost::class)
    ->setActionData(['title' => 'New title'])
    ->assertActionDataSet(['title' => 'New title'])
    ->assertHasActionErrors(['title'])
    ->assertHasNoActionErrors()
    ->callAction('save');
PHP;

    $violations = $this->scanCode(new DeprecatedTestMethodsRule, $code);

    $this->assertViolationCount(4, $violations);
    $this->assertViolationContains('setActionData()', $violations);
    $this->assertViolationContains('assertActionDataSet()', $violations);
    $this->assertViolationContains('assertHasActionErrors()', $violations);
    $this->assertViolationContains('assertHasNoActionErrors()', $violations);
});

it('marks :dataset as fixable and suggests the right replacement', function (string $deprecatedCall, string $expectedSuggestion, string $expectedReplacement, bool $requiresTestActionImport) {
    $code = <<<PHP
<?php

livewire(TestComponent::class)
    {$deprecatedCall};
PHP;

    $violations = $this->scanCode(new DeprecatedTestMethodsRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationIsFixable($violations);

    expect($violations[0]->suggestion)->toBe($expectedSuggestion);

    $fixedCode = $this->scanAndFix(new DeprecatedTestMethodsRule, $code);

    expect($fixedCode)->toContain($expectedReplacement);
    expect($fixedCode)->not->toContain($deprecatedCall);

    if ($requiresTestActionImport) {
        expect($fixedCode)->toContain("use Filament\\Actions\\Testing\\TestAction;\n");
    } else {
        expect($fixedCode)->not->toContain('use Filament\Actions\Testing\TestAction;');
    }
})->with('fixableDeprecatedTestMethods');

it('adds the TestAction import only once when it is already present', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Testing\TestAction;

livewire(TestComponent::class)
    ->mountTableAction('header_with_form')
    ->assertTableActionMounted('header_with_form');
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedTestMethodsRule, $code);

    expect(substr_count($fixedCode, 'use Filament\Actions\Testing\TestAction;'))->toBe(1);
    expect($fixedCode)->toContain("->mountAction(TestAction::make('header_with_form')->table())");
    expect($fixedCode)->toContain("->assertActionMounted(TestAction::make('header_with_form')->table())");
});

it('does not mark :dataset as fixable', function (string $deprecatedCall, string $expectedSuggestion) {
    $code = <<<PHP
<?php

livewire(TestComponent::class)
    {$deprecatedCall};
PHP;

    $violations = $this->scanCode(new DeprecatedTestMethodsRule, $code);

    $this->assertViolationCount(1, $violations);

    expect($violations[0]->isFixable)->toBeFalse();
    expect($violations[0]->suggestion)->toBe($expectedSuggestion);
})->with('nonFixableDeprecatedTestMethods');

it('passes when deprecated test methods are not used', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Testing\TestAction;

livewire(EditPost::class)
    ->fillForm(['title' => 'New title'])
    ->assertSchemaStateSet(['title' => 'New title'])
    ->assertHasFormErrors(['title'])
    ->assertHasNoFormErrors()
    ->callAction('save');

livewire(ListPosts::class)
    ->mountAction(TestAction::make('edit')->table())
    ->assertHasFormErrors(['title'])
    ->callAction(TestAction::make('delete')->table())
    ->assertActionVisible(TestAction::make('delete')->table())
    ->selectTableRecords([1, 2])
    ->callAction(TestAction::make('delete')->table()->bulk(), data: [])
    ->assertActionVisible(TestAction::make('delete')->table()->bulk());

livewire(EditPost::class)
    ->assertSchemaStateSet(['title' => 'Draft'])
    ->assertSchemaExists('form')
    ->assertSchemaComponentHidden('title', 'form')
    ->mountAction(TestAction::make('edit')->schemaComponent('author', 'form'))
    ->callAction(TestAction::make('edit')->schemaComponent('author', 'form'), data: ['name' => 'Taylor'])
    ->assertHasFormErrors(['name'])
    ->assertActionVisible(TestAction::make('edit')->schemaComponent('author', 'form'));

livewire(ViewPost::class)
    ->mountAction(TestAction::make('send')->schemaComponent('author', 'infolist'))
    ->callAction(TestAction::make('send')->schemaComponent('author', 'infolist'), data: ['note' => null])
    ->assertActionVisible(TestAction::make('send')->schemaComponent('author', 'infolist'))
    ->assertHasFormErrors(['note'])
    ->assertActionHasIcon(TestAction::make('send')->schemaComponent('author', 'infolist'), 'heroicon-o-envelope')
    ->assertActionShouldOpenUrlInNewTab(TestAction::make('send')->schemaComponent('author', 'infolist'));
PHP;

    $violations = $this->scanCode(new DeprecatedTestMethodsRule, $code);

    $this->assertNoViolations($violations);
});
