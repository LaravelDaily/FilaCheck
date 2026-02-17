<?php

use Filacheck\Rules\DeprecatedViewPropertyRule;

it('detects public $view property', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    public string $view = 'filament.pages.test';
}
PHP;

    $violations = $this->scanCode(new DeprecatedViewPropertyRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('$view', $violations);
});

it('detects untyped $view property', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    protected $view = 'filament.pages.test';
}
PHP;

    $violations = $this->scanCode(new DeprecatedViewPropertyRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('detects static $view property', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    protected static string $view = 'filament.pages.test';
}
PHP;

    $violations = $this->scanCode(new DeprecatedViewPropertyRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('passes for correct declaration', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    protected string $view = 'filament.pages.test';
}
PHP;

    $violations = $this->scanCode(new DeprecatedViewPropertyRule, $code);

    $this->assertNoViolations($violations);
});

it('ignores non-view properties', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    protected string $title = 'Test Page';
}
PHP;

    $violations = $this->scanCode(new DeprecatedViewPropertyRule, $code);

    $this->assertNoViolations($violations);
});

it('detects private $view property', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    private string $view = 'filament.pages.test';
}
PHP;

    $violations = $this->scanCode(new DeprecatedViewPropertyRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    public string $view = 'filament.pages.test';
}
PHP;

    $violations = $this->scanCode(new DeprecatedViewPropertyRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes public string $view to protected string $view', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    public string $view = 'filament.pages.test';
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedViewPropertyRule, $code);

    expect($fixedCode)->toContain('protected string $view');
    expect($fixedCode)->not->toContain('public string $view');
});

it('fixes untyped protected $view to protected string $view', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    protected $view = 'filament.pages.test';
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedViewPropertyRule, $code);

    expect($fixedCode)->toContain('protected string $view');
});

it('fixes static $view to protected string $view', function () {
    $code = <<<'PHP'
<?php

class TestPage
{
    protected static string $view = 'filament.pages.test';
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedViewPropertyRule, $code);

    expect($fixedCode)->toContain('protected string $view');
    expect($fixedCode)->not->toContain('static');
});
