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
