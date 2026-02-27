<?php

use Filacheck\Rules\DeprecatedFormAssertsRule;

it('detects deprecated form assert methods using a hardcoded deprecation map', function () {
    $code = <<<'PHP'
<?php

livewire(EditPost::class)
    ->assertFormSet(['title' => 'Draft'])
    ->assertFormExists('form')
    ->assertFormFieldHidden('title')
    ->mountFormComponentAction('author', 'edit')
    ->callFormComponentAction('author', 'edit', ['name' => null])
    ->assertHasFormComponentActionErrors(['name'])
    ->assertFormComponentActionVisible('author', 'edit');
PHP;

    $violations = $this->scanCode(new DeprecatedFormAssertsRule, $code);

    $this->assertViolationCount(7, $violations);
    $this->assertViolationContains('assertFormSet()', $violations);
    $this->assertViolationContains('assertFormExists()', $violations);
    $this->assertViolationContains('assertFormFieldHidden()', $violations);
    $this->assertViolationContains('mountFormComponentAction()', $violations);
    $this->assertViolationContains('callFormComponentAction()', $violations);
    $this->assertViolationContains('assertHasFormComponentActionErrors()', $violations);
    $this->assertViolationContains('assertFormComponentActionVisible()', $violations);

    $suggestionsByMessage = [];

    foreach ($violations as $violation) {
        $suggestionsByMessage[$violation->message] = $violation->suggestion;
    }

    expect($suggestionsByMessage['The `assertFormSet()` method is deprecated.'] ?? null)
        ->toBe('Use `assertSchemaStateSet()` instead.');

    expect($suggestionsByMessage['The `assertFormExists()` method is deprecated.'] ?? null)
        ->toBe('Use `assertSchemaExists()` instead.');

    expect($suggestionsByMessage['The `assertFormFieldHidden()` method is deprecated.'] ?? null)
        ->toBe('Use `assertSchemaComponentHidden()` instead.');

    expect($suggestionsByMessage['The `mountFormComponentAction()` method is deprecated.'] ?? null)
        ->toBe('Use `mountAction(TestAction::make(...)->schemaComponent(...))` instead.');

    expect($suggestionsByMessage['The `callFormComponentAction()` method is deprecated.'] ?? null)
        ->toBe('Use `callAction(TestAction::make(...)->schemaComponent(...), data: [...])` instead.');

    expect($suggestionsByMessage['The `assertHasFormComponentActionErrors()` method is deprecated.'] ?? null)
        ->toBe('Use `assertHasFormErrors()` instead.');

    expect($suggestionsByMessage['The `assertFormComponentActionVisible()` method is deprecated.'] ?? null)
        ->toBe('Use `assertActionVisible(TestAction::make(...)->schemaComponent(...))` instead.');
});

it('passes when non-deprecated form assertion methods are used', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Testing\TestAction;

livewire(EditPost::class)
    ->assertSchemaStateSet(['title' => 'Draft'])
    ->assertSchemaExists('form')
    ->assertSchemaComponentHidden('title', 'form')
    ->mountAction(TestAction::make('edit')->schemaComponent('author', 'form'))
    ->callAction(TestAction::make('edit')->schemaComponent('author', 'form'), data: ['name' => 'Taylor'])
    ->assertHasFormErrors(['name'])
    ->assertActionVisible(TestAction::make('edit')->schemaComponent('author', 'form'));
PHP;

    $violations = $this->scanCode(new DeprecatedFormAssertsRule, $code);

    $this->assertNoViolations($violations);
});
