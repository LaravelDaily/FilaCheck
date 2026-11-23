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

it('does not duplicate the import when the old Set import and callable $set are both used', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;

class TestResource
{
    public function fields(): array
    {
        return [
            TextInput::make('a')->afterStateUpdated(function (callable $set) { return $set('b', 1); }),
            TextInput::make('b')->afterStateUpdated(fn (Set $set) => $set('b', 1)),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsSetRule, $code);

    expect(substr_count($fixedCode, 'use Filament\Schemas\Components\Utilities\Set;'))->toBe(1);
    expect($fixedCode)->not->toContain('use Filament\Forms\Set;');
    expect($fixedCode)->toContain('function (Set $set)');
    $this->assertCodeResolves($fixedCode);
});

it('detects and fixes callable $set in arrow functions', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function fields(): array
    {
        return [
            TextInput::make('a')->afterStateUpdated(fn (callable $set) => $set('b', 1)),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationIsFixable($violations);

    $fixedCode = $this->scanAndFix(new DeprecatedFormsSetRule, $code);

    expect($fixedCode)->toContain('fn (Set $set)');
    expect($fixedCode)->toContain('use Filament\Schemas\Components\Utilities\Set;');
    $this->assertCodeResolves($fixedCode);
});

it('reuses an existing new Set import for callable $set', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Schemas\Components\Utilities\Set;

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $set) => $set('b', 1)];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsSetRule, $code);

    expect(substr_count($fixedCode, 'use Filament\Schemas\Components\Utilities\Set;'))->toBe(1);
    expect($fixedCode)->toContain('fn (Set $set)');
    $this->assertCodeResolves($fixedCode);
});

it('detects an existing new Set import inside a group use', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Schemas\Components\Utilities\{Get, Set};

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $set) => $set('b', 1)];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedFormsSetRule, $code);

    expect($fixedCode)->not->toContain('use Filament\Schemas\Components\Utilities\Set;');
    expect($fixedCode)->toContain('fn (Set $set)');
    $this->assertCodeResolves($fixedCode);
});

it('requires a manual fix for callable $set when Set is imported from an unrelated class', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use App\Support\Set;

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $set) => $set('b', 1)];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->isFixable)->toBeFalse();
    expect($violations[0]->suggestion)->toContain('App\Support\Set');

    expect($this->scanAndFix(new DeprecatedFormsSetRule, $code))->toBe($code);
});

it('requires a manual fix for callable $set when the old Set is group imported', function () {
    $code = <<<'PHP'
<?php

namespace App\Filament;

use Filament\Forms\{Set};

class TestResource
{
    public function fields(): array
    {
        return [fn (callable $set) => $set('b', 1)];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedFormsSetRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->isFixable)->toBeFalse();
});
