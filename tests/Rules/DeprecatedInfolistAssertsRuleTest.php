<?php

use Filacheck\Rules\DeprecatedInfolistAssertsRule;

it('detects deprecated infolist assert methods using a hardcoded deprecation map', function () {
    $code = <<<'PHP'
<?php

livewire(ViewPost::class)
    ->mountInfolistAction('author', 'send')
    ->callInfolistAction('author', 'send', ['note' => null])
    ->assertInfolistActionVisible('author', 'send')
    ->assertHasInfolistActionErrors(['note'])
    ->assertInfolistActionHasIcon('author', 'send', 'heroicon-o-envelope')
    ->assertInfolistActionShouldOpenUrlInNewTab('author', 'send');
PHP;

    $violations = $this->scanCode(new DeprecatedInfolistAssertsRule, $code);

    $this->assertViolationCount(6, $violations);
    $this->assertViolationContains('mountInfolistAction()', $violations);
    $this->assertViolationContains('callInfolistAction()', $violations);
    $this->assertViolationContains('assertInfolistActionVisible()', $violations);
    $this->assertViolationContains('assertHasInfolistActionErrors()', $violations);
    $this->assertViolationContains('assertInfolistActionHasIcon()', $violations);
    $this->assertViolationContains('assertInfolistActionShouldOpenUrlInNewTab()', $violations);

    $suggestionsByMessage = [];

    foreach ($violations as $violation) {
        $suggestionsByMessage[$violation->message] = $violation->suggestion;
    }

    expect($suggestionsByMessage['The `mountInfolistAction()` method is deprecated.'] ?? null)
        ->toBe('Use `mountAction(TestAction::make(...)->schemaComponent(...))` instead.');

    expect($suggestionsByMessage['The `callInfolistAction()` method is deprecated.'] ?? null)
        ->toBe('Use `callAction(TestAction::make(...)->schemaComponent(...), data: [...])` instead.');

    expect($suggestionsByMessage['The `assertInfolistActionVisible()` method is deprecated.'] ?? null)
        ->toBe('Use `assertActionVisible(TestAction::make(...)->schemaComponent(...))` instead.');

    expect($suggestionsByMessage['The `assertHasInfolistActionErrors()` method is deprecated.'] ?? null)
        ->toBe('Use `assertHasFormErrors()` instead.');

    expect($suggestionsByMessage['The `assertInfolistActionHasIcon()` method is deprecated.'] ?? null)
        ->toBe('Use `assertActionHasIcon(TestAction::make(...)->schemaComponent(...), ...)` instead.');

    expect($suggestionsByMessage['The `assertInfolistActionShouldOpenUrlInNewTab()` method is deprecated.'] ?? null)
        ->toBe('Use `assertActionShouldOpenUrlInNewTab(TestAction::make(...)->schemaComponent(...))` instead.');
});

it('passes when non-deprecated infolist assertion methods are used', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Testing\TestAction;

livewire(ViewPost::class)
    ->mountAction(TestAction::make('send')->schemaComponent('author', 'infolist'))
    ->callAction(TestAction::make('send')->schemaComponent('author', 'infolist'), data: ['note' => null])
    ->assertActionVisible(TestAction::make('send')->schemaComponent('author', 'infolist'))
    ->assertHasFormErrors(['note'])
    ->assertActionHasIcon(TestAction::make('send')->schemaComponent('author', 'infolist'), 'heroicon-o-envelope')
    ->assertActionShouldOpenUrlInNewTab(TestAction::make('send')->schemaComponent('author', 'infolist'));
PHP;

    $violations = $this->scanCode(new DeprecatedInfolistAssertsRule, $code);

    $this->assertNoViolations($violations);
});
