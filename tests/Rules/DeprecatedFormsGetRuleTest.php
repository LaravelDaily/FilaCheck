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

it('does not duplicate the import when the old Get import and callable $get are both used', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

class TestResource
{
    public function fields(): array
    {
        return [
            TextInput::make('a')->visible(function (callable $get) { return $get('b'); }),
            TextInput::make('b')->visible(fn (Get $get) => $get('b')),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsGetRule, $code);

    expect(substr_count($fixedCode, 'use Filament\Schemas\Components\Utilities\Get;'))->toBe(1);
    expect($fixedCode)->not->toContain('use Filament\Forms\Get;');
    expect($fixedCode)->toContain('function (Get $get)');
    $this->assertCodeResolves($fixedCode);
});

it('detects and fixes callable $get in arrow functions', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function fields(): array
    {
        return [
            TextInput::make('a')->visible(fn (callable $get) => $get('b')),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationIsFixable($violations);

    $fixedCode = $this->scanAndFix(new DeprecatedFormsGetRule, $code);

    expect($fixedCode)->toContain('fn (Get $get)');
    expect($fixedCode)->toContain('use Filament\Schemas\Components\Utilities\Get;');
    $this->assertCodeResolves($fixedCode);
});

it('reuses an existing new Get import for callable $get', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Schemas\Components\Utilities\Get;

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $get) => $get('b')];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsGetRule, $code);

    expect(substr_count($fixedCode, 'use Filament\Schemas\Components\Utilities\Get;'))->toBe(1);
    expect($fixedCode)->toContain('fn (Get $get)');
    $this->assertCodeResolves($fixedCode);
});

it('detects an existing new Get import inside a group use', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Schemas\Components\Utilities\{Get, Set};

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $get) => $get('b')];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsGetRule, $code);

    expect($fixedCode)->not->toContain('use Filament\Schemas\Components\Utilities\Get;');
    expect($fixedCode)->toContain('fn (Get $get)');
    $this->assertCodeResolves($fixedCode);
});

it('requires a manual fix for callable $get when Get is imported from an unrelated class', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use App\Support\Get;

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $get) => $get('b')];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->isFixable)->toBeFalse();
    expect($violations[0]->suggestion)->toContain('App\Support\Get');

    expect($this->scanAndFix(new DeprecatedFormsGetRule, $code))->toBe($code);
});

it('requires a manual fix for callable $get when the old Get is group imported', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Forms\{Get};

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $get) => $get('b')];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsGetRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->isFixable)->toBeFalse();
});
