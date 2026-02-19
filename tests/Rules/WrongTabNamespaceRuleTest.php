<?php

use Filacheck\Rules\WrongTabNamespaceRule;

it('detects wrong Filament\Schemas\Components\Tab import', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Wrong namespace', $violations);
});

it('passes with correct Filament\Schemas\Components\Tabs\Tab import', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Tabs\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertNoViolations($violations);
});

it('fixes wrong namespace to correct one', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new WrongTabNamespaceRule, $code);

    expect($fixedCode)->toContain('use Filament\Schemas\Components\Tabs\Tab;');
    expect($fixedCode)->not->toContain('use Filament\Schemas\Components\Tab;');
});

it('detects v3-style Tabs\Tab::make() and fixes to Tab::make()', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Tabs;

class TestResource
{
    public function form(): void
    {
        Tabs\Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Tabs\Tab::make()', $violations);

    $fixedCode = $this->scanAndFix(new WrongTabNamespaceRule, $code);

    expect($fixedCode)->toContain('Tab::make(');
    expect($fixedCode)->not->toContain('Tabs\Tab::make(');
    expect($fixedCode)->toContain('use Filament\Schemas\Components\Tabs\Tab;');
});

it('adds missing Tab import when Tab::make() used without import', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Tabs;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertNoViolations($violations);

    $fixedCode = $this->scanAndFix(new WrongTabNamespaceRule, $code);

    expect($fixedCode)->toContain('use Filament\Schemas\Components\Tabs\Tab;');
});

it('does not report violation when correct import and Tab::make() usage', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Tabs\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
        Tab::make('Advanced');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertNoViolations($violations);
});

it('detects v3 Forms\Components\Tab import and fixes it', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Wrong namespace', $violations);

    $fixedCode = $this->scanAndFix(new WrongTabNamespaceRule, $code);

    expect($fixedCode)->toContain('use Filament\Schemas\Components\Tabs\Tab;');
    expect($fixedCode)->not->toContain('use Filament\Forms\Components\Tab;');
});

it('detects v3 Forms\Components\Tabs\Tab import and fixes it', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\Tabs\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Wrong namespace', $violations);

    $fixedCode = $this->scanAndFix(new WrongTabNamespaceRule, $code);

    expect($fixedCode)->toContain('use Filament\Schemas\Components\Tabs\Tab;');
    expect($fixedCode)->not->toContain('use Filament\Forms\Components\Tabs\Tab;');
});

it('ignores non-Filament Tab imports', function () {
    $code = <<<'PHP'
<?php

use App\Models\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertNoViolations($violations);
});

it('marks all violations as fixable', function () {
    $code = <<<'PHP'
<?php

use Filament\Schemas\Components\Tab;

class TestResource
{
    public function form(): void
    {
        Tab::make('Settings');
    }
}
PHP;

    $violations = $this->scanCode(new WrongTabNamespaceRule, $code);

    $this->assertViolationIsFixable($violations);
});
