<?php

use Filacheck\Rules\DeprecatedTableAssertsRule;

it('detects deprecated table assert methods using a hardcoded deprecation map', function () {
    $code = <<<'PHP'
<?php

livewire(ListPosts::class)
    ->mountTableAction('edit', 1)
    ->callTableAction('edit', 1)
    ->assertTableActionVisible('edit', 1)
    ->assertHasTableActionErrors(['title'])
    ->callTableBulkAction('delete', [])
    ->assertTableBulkActionVisible('delete');
PHP;

    $violations = $this->scanCode(new DeprecatedTableAssertsRule, $code);

    $this->assertViolationCount(6, $violations);
    $this->assertViolationContains('mountTableAction()', $violations);
    $this->assertViolationContains('callTableAction()', $violations);
    $this->assertViolationContains('assertTableActionVisible()', $violations);
    $this->assertViolationContains('assertHasTableActionErrors()', $violations);
    $this->assertViolationContains('callTableBulkAction()', $violations);
    $this->assertViolationContains('assertTableBulkActionVisible()', $violations);

    $suggestionsByMessage = [];

    foreach ($violations as $violation) {
        $suggestionsByMessage[$violation->message] = $violation->suggestion;
    }

    expect($suggestionsByMessage['The `mountTableAction()` method is deprecated.'] ?? null)
        ->toBe('Use `mountAction(TestAction::make(...)->table(...))` instead.');

    expect($suggestionsByMessage['The `assertHasTableActionErrors()` method is deprecated.'] ?? null)
        ->toBe('Use `assertHasFormErrors()` instead.');

    expect($suggestionsByMessage['The `callTableAction()` method is deprecated.'] ?? null)
        ->toBe('Use `callAction(TestAction::make(...)->table(...), data: [...])` instead.');

    expect($suggestionsByMessage['The `assertTableActionVisible()` method is deprecated.'] ?? null)
        ->toBe('Use `assertActionVisible(TestAction::make(...)->table())` instead.');

    expect($suggestionsByMessage['The `callTableBulkAction()` method is deprecated.'] ?? null)
        ->toBe('Use `selectTableRecords([...])->callAction(TestAction::make(...)->table()->bulk(), data: [...])` instead.');

    expect($suggestionsByMessage['The `assertTableBulkActionVisible()` method is deprecated.'] ?? null)
        ->toBe('Use `assertActionVisible(TestAction::make(...)->table()->bulk())` instead.');
});

it('passes when non-deprecated table assertion methods are used', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Testing\TestAction;

livewire(ListPosts::class)
    ->mountAction(TestAction::make('edit')->table())
    ->assertHasFormErrors(['title'])
    ->callAction(TestAction::make('delete')->table())
    ->assertActionVisible(TestAction::make('delete')->table())
    ->selectTableRecords([1, 2])
    ->callAction(TestAction::make('delete')->table()->bulk(), data: [])
    ->assertActionVisible(TestAction::make('delete')->table()->bulk());
PHP;

    $violations = $this->scanCode(new DeprecatedTableAssertsRule, $code);

    $this->assertNoViolations($violations);
});
