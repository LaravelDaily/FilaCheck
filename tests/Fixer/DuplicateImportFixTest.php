<?php

use Filacheck\FilacheckServiceProvider;
use Filacheck\Fixer\CodeFixer;
use Filacheck\Scanner\ResourceScanner;

// Regression tests for https://github.com/LaravelDaily/filacheck/issues/52:
// auto-fix added an import whose short name the file already imported, which
// PHP rejects with "Cannot use ... because the name is already in use".

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/filacheck-duplicate-imports-' . uniqid('', true);
    mkdir($this->tempDir, 0755, true);

    foreach (glob(__DIR__ . '/fixtures/duplicate-imports/*.php') as $fixture) {
        copy($fixture, $this->tempDir . '/' . basename($fixture));
    }

    $this->scanner = new ResourceScanner;

    foreach (FilacheckServiceProvider::rules() as $ruleClass) {
        $this->scanner->addRule(new $ruleClass);
    }
});

afterEach(function () {
    foreach (glob($this->tempDir . '/*') as $file) {
        unlink($file);
    }

    rmdir($this->tempDir);
});

it('leaves every file loadable after fixing with all rules', function () {
    (new CodeFixer)->fix($this->scanner->scan($this->tempDir));

    foreach (glob($this->tempDir . '/*.php') as $file) {
        $this->assertCodeResolves(file_get_contents($file));
    }
});

it('reuses or renames existing imports instead of duplicating them', function () {
    (new CodeFixer)->fix($this->scanner->scan($this->tempDir));

    $form = file_get_contents($this->tempDir . '/UserForm.php');
    expect(substr_count($form, 'use Filament\Schemas\Components\Utilities\Get;'))->toBe(1);
    expect($form)->not->toContain('use Filament\Forms\Get;');
    expect($form)->toContain('function (Get $get)');

    $table = file_get_contents($this->tempDir . '/UsersTable.php');
    expect(substr_count($table, 'use Filament\Actions\BulkAction;'))->toBe(1);
    expect($table)->not->toContain('use Filament\Tables\Actions\BulkAction;');
    expect($table)->toContain("BulkAction::make('export')");

    $list = file_get_contents($this->tempDir . '/ListUsers.php');
    expect(substr_count($list, 'Builder;'))->toBe(1);
    expect($list)->toContain('use Illuminate\Contracts\Database\Eloquent\Builder;');
    expect($list)->toContain("->query(fn (): Builder => User::query()->whereNotNull('email_verified_at'))");
    expect($list)->not->toContain('getTableQuery');
});

it('finds nothing left to fix on a second run', function () {
    (new CodeFixer)->fix($this->scanner->scan($this->tempDir));

    $fixable = array_filter(
        $this->scanner->scan($this->tempDir),
        fn ($violation) => $violation->isFixable,
    );

    expect($fixable)->toBe([]);
});
