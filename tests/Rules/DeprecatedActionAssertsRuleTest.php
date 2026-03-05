<?php

use Filacheck\Rules\DeprecatedActionAssertsRule;

it('detects deprecated assert methods using a hardcoded deprecation map', function () {
    $code = <<<'PHP'
<?php

livewire(EditPost::class)
    ->setActionData(['title' => 'New title'])
    ->assertActionDataSet(['title' => 'New title'])
    ->assertHasActionErrors(['title'])
    ->assertHasNoActionErrors()
    ->callAction('save');
PHP;

    $violations = $this->scanCode(new DeprecatedActionAssertsRule, $code);

    $this->assertViolationCount(4, $violations);
    $this->assertViolationContains('setActionData()', $violations);
    $this->assertViolationContains('assertActionDataSet()', $violations);
    $this->assertViolationContains('assertHasActionErrors()', $violations);
    $this->assertViolationContains('assertHasNoActionErrors()', $violations);

    $suggestionsByMessage = [];

    foreach ($violations as $violation) {
        $suggestionsByMessage[$violation->message] = $violation->suggestion;
    }

    expect($suggestionsByMessage['The `setActionData()` method is deprecated.'] ?? null)
        ->toBe('Use `fillForm()` instead.');

    expect($suggestionsByMessage['The `assertActionDataSet()` method is deprecated.'] ?? null)
        ->toBe('Use `assertSchemaStateSet()` instead.');

    expect($suggestionsByMessage['The `assertHasActionErrors()` method is deprecated.'] ?? null)
        ->toBe('Use `assertHasFormErrors()` instead.');

    expect($suggestionsByMessage['The `assertHasNoActionErrors()` method is deprecated.'] ?? null)
        ->toBe('Use `assertHasNoFormErrors()` instead.');
});

it('passes when deprecated assert methods are not used', function () {
    $code = <<<'PHP'
<?php

livewire(EditPost::class)
    ->fillForm(['title' => 'New title'])
    ->assertSchemaStateSet(['title' => 'New title'])
    ->assertHasFormErrors(['title'])
    ->assertHasNoFormErrors()
    ->callAction('save');
PHP;

    $violations = $this->scanCode(new DeprecatedActionAssertsRule, $code);

    $this->assertNoViolations($violations);
});
