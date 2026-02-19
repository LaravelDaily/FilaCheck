<?php

use Filacheck\Rules\DeprecatedFormsSetRule;

it('detects deprecated Filament\Forms\Set usage', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Set;

class TestResource
{
    public function form(Set $set): void
    {
        $set('name', 'value');
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Filament\Forms\Set', $violations);
});

it('passes when new namespace is used', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Utilities\Set;

class TestResource
{
    public function form(Set $set): void
    {
        $set('name', 'value');
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertNoViolations($violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Set;

class TestResource
{
    public function form(Set $set): void {}
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes deprecated namespace to new namespace', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Set;

class TestResource
{
    public function form(Set $set): void {}
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsSetRule, $code);

    expect($fixedCode)->toContain('use Filament\Schemas\Components\Utilities\Set;');
    expect($fixedCode)->not->toContain('use Filament\Forms\Set;');
});

it('detects callable $set in a closure', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->afterStateUpdated(function (callable $set) {
                    $set('slug', 'value');
                }),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('$set', $violations);
});

it('passes when Set type hint is used in closure', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->afterStateUpdated(function (Set $set) {
                    $set('slug', 'value');
                }),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertNoViolations($violations);
});

it('fixes callable $set to Set $set and adds import', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->afterStateUpdated(function (callable $set) {
                    $set('slug', 'value');
                }),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsSetRule, $code);

    expect($fixedCode)->toContain('function (Set $set)');
    expect($fixedCode)->not->toContain('callable $set');
    expect($fixedCode)->toContain('use Filament\Schemas\Components\Utilities\Set;');
});
