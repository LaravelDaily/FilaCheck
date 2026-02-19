<?php

use Filacheck\Rules\ActionInBulkActionGroupRule;

it('detects Action::make() inside BulkActionGroup::make()', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve')
                        ->label('Approve Selected'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Action::make()', $violations);
});

it('passes when BulkAction::make() is used inside BulkActionGroup', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approve Selected'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple Action::make() inside BulkActionGroup', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve')
                        ->label('Approve Selected'),
                    Action::make('reject')
                        ->label('Reject Selected'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertViolationCount(2, $violations);
});

it('ignores Action::make() outside toolbarActions', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('create')
                    ->label('Create'),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertNoViolations($violations);
});

it('ignores Action::make() in recordActions chained with toolbarActions', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->recordActions([
                Action::make('view'),
                Action::make('edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertNoViolations($violations);
});

it('only flags Action::make() in toolbarActions, not in other chained methods', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->recordActions([
                Action::make('view'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertViolationCount(1, $violations);
});

it('ignores Action::make() outside table method', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;

class TestResource
{
    public function form(): array
    {
        return [
            Action::make('test'),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertNoViolations($violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes Action::make() to BulkAction::make()', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve')
                        ->label('Approve Selected'),
                ]),
            ]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new ActionInBulkActionGroupRule, $code);

    expect($fixedCode)->toContain('BulkAction::make(\'approve\')');
    expect($fixedCode)->not->toMatch('/[^k]Action::make/');
});

it('adds BulkAction import when fixing', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve'),
                ]),
            ]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new ActionInBulkActionGroupRule, $code);

    expect($fixedCode)->toContain('use Filament\Actions\BulkAction;');
});

it('does not duplicate BulkAction import if already present', function () {
    $code = <<<'PHP'
<?php

use Filament\Actions\BulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve'),
                ]),
            ]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new ActionInBulkActionGroupRule, $code);

    expect(substr_count($fixedCode, 'use Filament\Actions\BulkAction;'))->toBe(1);
});

it('detects Action::make() directly inside toolbarActions without BulkActionGroup', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                Action::make('approve')
                    ->label('Approve Selected'),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Action::make()', $violations);
});

it('fixes Action::make() directly inside toolbarActions', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class TestResource
{
    public function table(Table $table): Table
    {
        return $table
            ->toolbarActions([
                Action::make('approve')
                    ->label('Approve Selected'),
            ]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new ActionInBulkActionGroupRule, $code);

    expect($fixedCode)->toContain('BulkAction::make(\'approve\')');
    expect($fixedCode)->not->toMatch('/[^k]Action::make/');
    expect($fixedCode)->toContain('use Filament\Actions\BulkAction;');
});

it('detects Action::make() in a non-table method with Table type-hinted parameter', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('approve')
                        ->label('Approve Selected'),
                ]),
            ]);
    }
}
PHP;

    $violations = $this->scanCode(new ActionInBulkActionGroupRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Action::make()', $violations);
});
