<?php

use Filacheck\Rules\DeprecatedFormsGetRule;

it('detects deprecated Filament\Forms\Get usage', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Get;

class TestResource
{
    public function form(Get $get): void
    {
        $get('name');
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Filament\Forms\Get', $violations);
});

it('passes when new namespace is used', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Utilities\Get;

class TestResource
{
    public function form(Get $get): void
    {
        $get('name');
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertNoViolations($violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Get;

class TestResource
{
    public function form(Get $get): void {}
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes deprecated namespace to new namespace', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Get;

class TestResource
{
    public function form(Get $get): void {}
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsGetRule, $code);

    expect($fixedCode)->toContain('use Filament\Schemas\Components\Utilities\Get;');
    expect($fixedCode)->not->toContain('use Filament\Forms\Get;');
});

it('detects callable $get in a closure', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->afterStateUpdated(function (callable $get) {
                    $get('slug');
                }),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('$get', $violations);
});

it('passes when Get type hint is used in closure', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->afterStateUpdated(function (Get $get) {
                    $get('slug');
                }),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertNoViolations($violations);
});

it('fixes callable $get to Get $get and adds import', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->afterStateUpdated(function (callable $get) {
                    $get('slug');
                }),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsGetRule, $code);

    expect($fixedCode)->toContain('function (Get $get)');
    expect($fixedCode)->not->toContain('callable $get');
    expect($fixedCode)->toContain('use Filament\Schemas\Components\Utilities\Get;');
});
