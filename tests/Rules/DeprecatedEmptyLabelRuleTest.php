<?php

use Filacheck\Rules\DeprecatedEmptyLabelRule;

it('detects empty label string', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->label('')
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains("label('')", $violations);
});

it('passes when hiddenLabel is used', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->hiddenLabel()
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertNoViolations($violations);
});

it('passes when label has content', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple empty labels', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('a')->label(''),
            TextInput::make('b')->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

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
            TextInput::make('name')->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes empty label to hiddenLabel', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->label(''),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedEmptyLabelRule, $code);

    expect($fixedCode)->toContain('->hiddenLabel()');
    expect($fixedCode)->not->toContain("->label('')");
});
