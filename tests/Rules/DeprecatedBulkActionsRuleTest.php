<?php

use Filacheck\Rules\DeprecatedBulkActionsRule;

it('detects bulkActions method usage in table method', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('delete'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedBulkActionsRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('bulkActions()', $violations);
});

it('passes when toolbarActions is used instead of bulkActions', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedBulkActionsRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple bulkActions calls', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        $table->bulkActions([]);

        return $table->bulkActions([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedBulkActionsRule, $code);

    $this->assertViolationCount(2, $violations);
});

it('ignores bulkActions outside a method with Table parameter', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return $this->bulkActions([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedBulkActionsRule, $code);

    $this->assertNoViolations($violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table->bulkActions([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedBulkActionsRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes bulkActions to toolbarActions', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('delete'),
                ]),
            ]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedBulkActionsRule, $code);

    expect($fixedCode)->toContain('->toolbarActions(');
    expect($fixedCode)->not->toContain('->bulkActions(');
});

it('fixes multiple bulkActions occurrences', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        $table->bulkActions([]);

        return $table->bulkActions([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedBulkActionsRule, $code);

    expect(substr_count($fixedCode, '->toolbarActions('))->toBe(2);
    expect($fixedCode)->not->toContain('->bulkActions(');
});
