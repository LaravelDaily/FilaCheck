<?php

use Filacheck\Rules\DeprecatedReactiveRule;

it('detects reactive method usage', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->reactive()
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedReactiveRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('reactive()', $violations);
});

it('passes when live is used instead of reactive', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->live()
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedReactiveRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple reactive usages', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->reactive(),
            Select::make('type')->reactive(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedReactiveRule, $code);

    $this->assertViolationCount(2, $violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->reactive(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedReactiveRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes reactive to live', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->reactive(),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedReactiveRule, $code);

    expect($fixedCode)->toContain('->live()');
    expect($fixedCode)->not->toContain('->reactive()');
});

it('fixes multiple reactive usages', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->reactive(),
            Select::make('type')->reactive(),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedReactiveRule, $code);

    expect(substr_count($fixedCode, '->live()'))->toBe(2);
    expect($fixedCode)->not->toContain('->reactive()');
});
