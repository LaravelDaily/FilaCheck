<?php

use Filacheck\Rules\DeprecatedEmptyLabelRule;

it('detects empty label string', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->label('')
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains("label('')", $violations);
});

it('passes when hiddenLabel is used', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->hiddenLabel()
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertNoViolations($violations);
});

it('passes when label has content', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required(),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple empty labels', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('a')->label(''),
            TextInput::make('b')->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(2, $violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes empty label to hiddenLabel', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->label(''),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedEmptyLabelRule, $code);

    expect($fixedCode)->toContain('->hiddenLabel()');
    expect($fixedCode)->not->toContain("->label('')");
});

it('skips table columns with empty label', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\IconColumn;

class TestResource
{
    public function table(): array
    {
        return [
            IconColumn::make('status')->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertNoViolations($violations);
});

it('skips TextColumn with empty label', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\TextColumn;

class TestResource
{
    public function table(): array
    {
        return [
            TextColumn::make('name')->sortable()->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertNoViolations($violations);
});

it('still detects empty label on infolist entries', function () {
    $code = <<<'PHP'
<?php

use Filament\Infolists\Components\TextEntry;

class TestResource
{
    public function infolist(): array
    {
        return [
            TextEntry::make('name')->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('suggests iconButton for Action with empty label', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class TestResource
{
    public function getActions(): array
    {
        return [
            Action::make('edit')
                ->icon('heroicon-m-pencil')
                ->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->suggestion)->toContain('iconButton()');
});

it('suggests iconButton for EditAction with empty label', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\EditAction;

class TestResource
{
    public function table(): array
    {
        return [
            EditAction::make()->label(''),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->suggestion)->toContain('iconButton()');
});

it('fixes Action empty label to iconButton', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class TestResource
{
    public function getActions(): array
    {
        return [
            Action::make('edit')->icon('heroicon-m-pencil')->label(''),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedEmptyLabelRule, $code);

    expect($fixedCode)->toContain('->iconButton()');
    expect($fixedCode)->not->toContain("->label('')");
    expect($fixedCode)->not->toContain('->hiddenLabel()');
});

it('suggests iconButton for $this->label(\'\') in a class extending Action', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\Action;

class SendEmail extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-m-pencil')
            ->label('');
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->suggestion)->toContain('iconButton()');
});

it('skips $this->label(\'\') in a class extending a Column', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\TextColumn;

class StatusColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('');
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertNoViolations($violations);
});

it('suggests hiddenLabel for $this->label(\'\') in a class extending a form field', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;

class EmailInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('');
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedEmptyLabelRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->suggestion)->toContain('hiddenLabel()');
});
