<?php

use Filacheck\Rules\DeprecatedActionFormRule;

it('detects form method on actions', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class TestResource
{
    public function actions(): array
    {
        return [
            Action::make('send')
                ->form([
                    TextInput::make('email'),
                ])
                ->action(fn () => null),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('form()', $violations);
});

it('passes when schema is used instead of form', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class TestResource
{
    public function actions(): array
    {
        return [
            Action::make('send')
                ->schema([
                    TextInput::make('email'),
                ])
                ->action(fn () => null),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertNoViolations($violations);
});

it('detects form method on various action types', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;

class TestResource
{
    public function actions(): array
    {
        return [
            EditAction::make()->form([]),
            CreateAction::make()->form([]),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertViolationCount(2, $violations);
});

it('ignores form method on non-action classes', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function something(): array
    {
        return SomeOtherClass::make()->form([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertNoViolations($violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class TestResource
{
    public function actions(): array
    {
        return [
            Action::make('send')->form([]),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes form to schema', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class TestResource
{
    public function actions(): array
    {
        return [
            Action::make('send')->form([]),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedActionFormRule, $code);

    expect($fixedCode)->toContain('->schema([])');
    expect($fixedCode)->not->toContain('->form([])');
});

it('detects $this->form() in a class extending Action', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class SendEmail extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            TextInput::make('email'),
        ]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('form()', $violations);
});

it('detects $this->label()->form() chained in a class extending Action', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class SendEmail extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Send')
            ->form([
                TextInput::make('email'),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('form()', $violations);
});

it('detects $this->form() in classes extending various action types', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\EditAction;

class CustomEditAction extends EditAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->form([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('ignores $this->form() in a class that does not extend an action class', function () {
    $code = <<<'PHP'
<?php

class SomeComponent extends Component
{
    protected function setUp(): void
    {
        $this->form([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedActionFormRule, $code);

    $this->assertNoViolations($violations);
});

it('fixes $this->form() to $this->schema() in custom action classes', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class SendEmail extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            TextInput::make('email'),
        ]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedActionFormRule, $code);

    expect($fixedCode)->toContain('$this->schema([');
    expect($fixedCode)->not->toContain('$this->form([');
});
