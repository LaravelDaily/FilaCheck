<?php

use Filacheck\Rules\DeprecatedMutateFormDataUsingRule;

it('detects mutateFormDataUsing method', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\CreateAction;

class TestResource
{
    public function actions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(fn ($data) => $data),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedMutateFormDataUsingRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('mutateFormDataUsing()', $violations);
});

it('passes when mutateDataUsing is used', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\CreateAction;

class TestResource
{
    public function actions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn ($data) => $data),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedMutateFormDataUsingRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple usages', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function something()
    {
        $a->mutateFormDataUsing(fn () => []);
        $b->mutateFormDataUsing(fn () => []);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedMutateFormDataUsingRule, $code);

    $this->assertViolationCount(2, $violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function something()
    {
        $action->mutateFormDataUsing(fn () => []);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedMutateFormDataUsingRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes mutateFormDataUsing to mutateDataUsing', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function something()
    {
        $action->mutateFormDataUsing(fn () => []);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedMutateFormDataUsingRule, $code);

    expect($fixedCode)->toContain('->mutateDataUsing(fn () => [])');
    expect($fixedCode)->not->toContain('->mutateFormDataUsing(');
});
