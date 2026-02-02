<?php

use Filacheck\Rules\DeprecatedFilterFormRule;

it('detects form method on filters', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Filters\Filter;

class TestResource
{
    public function filters(): array
    {
        return [
            Filter::make('status')
                ->form([
                    Select::make('value'),
                ]),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFilterFormRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('form()', $violations);
});

it('passes when schema is used instead of form', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Filters\Filter;

class TestResource
{
    public function filters(): array
    {
        return [
            Filter::make('status')
                ->schema([
                    Select::make('value'),
                ]),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFilterFormRule, $code);

    $this->assertNoViolations($violations);
});

it('detects form method on SelectFilter', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Filters\SelectFilter;

class TestResource
{
    public function filters(): array
    {
        return [
            SelectFilter::make('type')->form([]),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFilterFormRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('ignores form method on non-filter classes', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function something(): array
    {
        return NotAFilter::make()->form([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFilterFormRule, $code);

    $this->assertNoViolations($violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Filters\Filter;

class TestResource
{
    public function filters(): array
    {
        return [
            Filter::make('status')->form([]),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFilterFormRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes form to schema', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Filters\Filter;

class TestResource
{
    public function filters(): array
    {
        return [
            Filter::make('status')->form([]),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFilterFormRule, $code);

    expect($fixedCode)->toContain('->schema([])');
    expect($fixedCode)->not->toContain('->form([])');
});
