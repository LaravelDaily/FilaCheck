<?php

use Filacheck\Rules\DeprecatedGetTableQueryRule;

it('detects getTableQuery method', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('getTableQuery()', $violations);
});

it('passes when no getTableQuery exists', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertNoViolations($violations);
});

it('marks as fixable when simple return and table method exist', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('marks as not fixable when body is complex', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        $query = Order::query();
        $query->where('active', true);

        return $query->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);

    foreach ($violations as $violation) {
        expect($violation->isFixable)->toBeFalse();
    }
});

it('marks as not fixable when no table method exists', function () {
    $code = <<<'PHP'
<?php

use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);

    foreach ($violations as $violation) {
        expect($violation->isFixable)->toBeFalse();
    }
});

it('marks as not fixable when table already has query call', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query())
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);

    foreach ($violations as $violation) {
        expect($violation->isFixable)->toBeFalse();
    }
});

it('fixes by removing getTableQuery and inserting query into table chain', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('->query(fn (): Builder => Order::query()->latest())');
    expect($fixedCode)->not->toContain('getTableQuery');
});

it('handles multi-line return expression', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()
            ->where('active', true)
            ->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('->query(fn (): Builder => Order::query()');
    expect($fixedCode)->toContain('->latest())');
    expect($fixedCode)->not->toContain('getTableQuery');
});

it('preserves indentation when inserting query', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    // The ->query() should have the same indentation as ->columns()
    expect($fixedCode)->toContain("            ->query(fn (): Builder => Order::query()->latest())\n            ->columns([])");
});

it('adds Builder import when missing', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;

class TestWidget
{
    protected function getTableQuery()
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('use Illuminate\Database\Eloquent\Builder;');
    expect($fixedCode)->toContain('->query(fn (): Builder => Order::query()->latest())');
});

it('does not duplicate Builder import when already present', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestWidget
{
    protected function getTableQuery(): Builder
    {
        return Order::query()->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([]);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect(substr_count($fixedCode, 'use Illuminate\Database\Eloquent\Builder;'))->toBe(1);
});

function getTableQueryFixture(string $imports, string $aboveMethod = ''): string
{
    return <<<PHP
<?php

namespace App\Filament;

use App\Models\User;
use Filament\Tables\Table;
{$imports}

class ListUsers
{
    public function table(Table \$table): Table
    {
        return \$table
            ->columns([]);
    }
{$aboveMethod}
    protected function getTableQuery()
    {
        return User::query()->latest();
    }
}

PHP;
}

it('reuses an imported Eloquent Builder contract instead of adding a second Builder import', function () {
    $code = getTableQueryFixture('use Illuminate\Contracts\Database\Eloquent\Builder;');

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('->query(fn (): Builder => User::query()->latest())');
    expect($fixedCode)->not->toContain('use Illuminate\Database\Eloquent\Builder;');
    expect(substr_count($fixedCode, 'Builder;'))->toBe(1);
    $this->assertCodeResolves($fixedCode);
});

it('reuses an existing Eloquent Builder import', function () {
    $code = getTableQueryFixture('use Illuminate\Database\Eloquent\Builder;');

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect(substr_count($fixedCode, 'use Illuminate\Database\Eloquent\Builder;'))->toBe(1);
    $this->assertCodeResolves($fixedCode);
});

it('detects an Eloquent Builder imported in a group use', function () {
    $code = getTableQueryFixture('use Illuminate\Database\Eloquent\{Builder, Model};');

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->not->toContain('use Illuminate\Database\Eloquent\Builder;');
    expect($fixedCode)->toContain('->query(fn (): Builder =>');
    $this->assertCodeResolves($fixedCode);
});

it('requires a manual fix when Builder is imported from an unrelated class', function () {
    $code = getTableQueryFixture('use Illuminate\Database\Query\Builder;');

    $violations = $this->scanCode(new DeprecatedGetTableQueryRule, $code);

    $this->assertViolationCount(1, $violations);
    expect($violations[0]->isFixable)->toBeFalse();
    expect($violations[0]->suggestion)->toContain('Illuminate\Database\Query\Builder');

    expect($this->scanAndFix(new DeprecatedGetTableQueryRule, $code))->toBe($code);
});

it('removes a line comment above getTableQuery without commenting out the class brace', function () {
    $code = getTableQueryFixture('', "\n    // Only verified users\n    // are listed.");

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->not->toContain('Only verified users');
    expect($fixedCode)->not->toContain('are listed.');
    expect($fixedCode)->not->toContain('getTableQuery');
    expect($fixedCode)->toEndWith("            ->columns([]);\n    }\n}\n");
    $this->assertCodeResolves($fixedCode);
});

it('removes a docblock above getTableQuery', function () {
    $code = getTableQueryFixture('', "\n    /**\n     * The table query.\n     */");

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->not->toContain('The table query.');
    expect($fixedCode)->not->toContain('/**');
    expect($fixedCode)->toEndWith("            ->columns([]);\n    }\n}\n");
    $this->assertCodeResolves($fixedCode);
});

it('removes an attribute above getTableQuery', function () {
    $code = getTableQueryFixture('', "\n    #[\\Override]");

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->not->toContain('Override');
    expect($fixedCode)->toEndWith("            ->columns([]);\n    }\n}\n");
    $this->assertCodeResolves($fixedCode);
});

it('keeps a trailing comment on the line before getTableQuery', function () {
    $code = str_replace(
        "            ->columns([]);\n    }\n",
        "            ->columns([]);\n    } // end of table()\n",
        getTableQueryFixture('', "\n    // Only verified users"),
    );

    $fixedCode = $this->scanAndFix(new DeprecatedGetTableQueryRule, $code);

    expect($fixedCode)->toContain('} // end of table()');
    expect($fixedCode)->not->toContain('Only verified users');
    $this->assertCodeResolves($fixedCode);
});
