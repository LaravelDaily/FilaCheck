<?php

use Filacheck\Fixer\CodeFixer;
use Filacheck\Support\Violation;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/filacheck-fixer-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    $files = glob($this->tempDir.'/*');
    foreach ($files as $file) {
        unlink($file);
    }
    rmdir($this->tempDir);
});

it('fixes a single violation', function () {
    $file = $this->tempDir.'/test.php';
    $content = '<?php $input->reactive();';
    file_put_contents($file, $content);

    $violations = [
        new Violation(
            level: 'warning',
            message: 'Test violation',
            file: $file,
            line: 1,
            isFixable: true,
            startPos: 14,
            endPos: 22,
            replacement: 'live',
        ),
    ];

    $fixer = new CodeFixer;
    $results = $fixer->fix($violations);

    expect($results['fixed'])->toBe(1);
    expect($results['skipped'])->toBe(0);
    expect(file_get_contents($file))->toBe('<?php $input->live();');
});

it('fixes multiple violations in the same file', function () {
    $file = $this->tempDir.'/test.php';
    // Position:  0         1         2         3
    //            0123456789012345678901234567890123456
    $content = '<?php $a->reactive(); $b->reactive();';
    file_put_contents($file, $content);

    // First 'reactive' starts at position 10, ends at 18
    // Second 'reactive' starts at position 26, ends at 34
    $violations = [
        new Violation(
            level: 'warning',
            message: 'Test violation 1',
            file: $file,
            line: 1,
            isFixable: true,
            startPos: 10,
            endPos: 18,
            replacement: 'live',
        ),
        new Violation(
            level: 'warning',
            message: 'Test violation 2',
            file: $file,
            line: 1,
            isFixable: true,
            startPos: 26,
            endPos: 34,
            replacement: 'live',
        ),
    ];

    $fixer = new CodeFixer;
    $results = $fixer->fix($violations);

    expect($results['fixed'])->toBe(2);
    expect(file_get_contents($file))->toBe('<?php $a->live(); $b->live();');
});

it('skips non-fixable violations', function () {
    $file = $this->tempDir.'/test.php';
    $content = '<?php $input->reactive();';
    file_put_contents($file, $content);

    $violations = [
        new Violation(
            level: 'warning',
            message: 'Non-fixable violation',
            file: $file,
            line: 1,
            isFixable: false,
        ),
    ];

    $fixer = new CodeFixer;
    $results = $fixer->fix($violations);

    expect($results['fixed'])->toBe(0);
    expect($results['skipped'])->toBe(1);
    expect(file_get_contents($file))->toBe($content);
});

it('creates backup files when requested', function () {
    $file = $this->tempDir.'/test.php';
    $content = '<?php $input->reactive();';
    file_put_contents($file, $content);

    $violations = [
        new Violation(
            level: 'warning',
            message: 'Test violation',
            file: $file,
            line: 1,
            isFixable: true,
            startPos: 14,
            endPos: 22,
            replacement: 'live',
        ),
    ];

    $fixer = new CodeFixer;
    $fixer->fix($violations, createBackup: true);

    expect(file_exists($file.'.bak'))->toBeTrue();
    expect(file_get_contents($file.'.bak'))->toBe($content);
    expect(file_get_contents($file))->toBe('<?php $input->live();');
});

it('does not create backup files by default', function () {
    $file = $this->tempDir.'/test.php';
    $content = '<?php $input->reactive();';
    file_put_contents($file, $content);

    $violations = [
        new Violation(
            level: 'warning',
            message: 'Test violation',
            file: $file,
            line: 1,
            isFixable: true,
            startPos: 14,
            endPos: 22,
            replacement: 'live',
        ),
    ];

    $fixer = new CodeFixer;
    $fixer->fix($violations, createBackup: false);

    expect(file_exists($file.'.bak'))->toBeFalse();
});

it('handles multiple files', function () {
    $file1 = $this->tempDir.'/test1.php';
    $file2 = $this->tempDir.'/test2.php';
    file_put_contents($file1, '<?php $a->reactive();');
    file_put_contents($file2, '<?php $b->reactive();');

    $violations = [
        new Violation(
            level: 'warning',
            message: 'Test 1',
            file: $file1,
            line: 1,
            isFixable: true,
            startPos: 10,
            endPos: 18,
            replacement: 'live',
        ),
        new Violation(
            level: 'warning',
            message: 'Test 2',
            file: $file2,
            line: 1,
            isFixable: true,
            startPos: 10,
            endPos: 18,
            replacement: 'live',
        ),
    ];

    $fixer = new CodeFixer;
    $results = $fixer->fix($violations);

    expect($results['fixed'])->toBe(2);
    expect(count($results['byFile']))->toBe(2);
    expect(file_get_contents($file1))->toBe('<?php $a->live();');
    expect(file_get_contents($file2))->toBe('<?php $b->live();');
});

it('returns fix results by file', function () {
    $file = $this->tempDir.'/test.php';
    file_put_contents($file, '<?php $input->reactive();');

    $violations = [
        new Violation(
            level: 'warning',
            message: 'Fixable',
            file: $file,
            line: 1,
            isFixable: true,
            startPos: 14,
            endPos: 22,
            replacement: 'live',
        ),
        new Violation(
            level: 'warning',
            message: 'Not fixable',
            file: $file,
            line: 1,
            isFixable: false,
        ),
    ];

    $fixer = new CodeFixer;
    $results = $fixer->fix($violations);

    expect($results['byFile'][$file]['fixed'])->toBe(1);
    expect($results['byFile'][$file]['skipped'])->toBe(1);
});
