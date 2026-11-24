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

it('does not modify files during dry run', function () {
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
    $results = $fixer->fix($violations, dryRun: true);

    expect($results['fixed'])->toBe(1);
    expect($results['dryRun'])->toBeTrue();
    expect(file_get_contents($file))->toBe($content);
});

it('returns change previews during dry run', function () {
    $file = $this->tempDir.'/test.php';
    file_put_contents($file, '<?php $input->reactive();');

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
    $results = $fixer->fix($violations, dryRun: true);

    expect($results['previews'])->toHaveKey($file)
        ->and($results['previews'][$file])->toHaveCount(1)
        ->and($results['previews'][$file][0]['line'])->toBe(1)
        ->and($results['previews'][$file][0]['from'])->toBe('reactive')
        ->and($results['previews'][$file][0]['to'])->toBe('live');
});

it('does not create backup files during dry run', function () {
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
    $fixer->fix($violations, createBackup: true, dryRun: true);

    expect(file_exists($file.'.bak'))->toBeFalse();
    expect(file_get_contents($file))->toBe($content);
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

function makeCommandFixture(string $tempDir): string
{
    $project = $tempDir.'/project-'.uniqid();
    mkdir($project, 0755, true);
    file_put_contents($project.'/artisan', "#!/usr/bin/env php\n<?php\n");

    return $project;
}

it('runs a command-style fix and counts it as fixed', function () {
    $project = makeCommandFixture($this->tempDir);
    $marker = $this->tempDir.'/ran-'.uniqid();

    $violations = [
        new Violation(
            level: 'warning',
            message: 'needs scaffolding',
            file: $project.'/some/blade.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: [PHP_BINARY, '-r', 'touch($argv[1]);', $marker],
            fixCommandCwd: $project,
        ),
    ];

    $results = (new CodeFixer)->fix($violations);

    expect($results['fixed'])->toBe(1);
    expect($results['skipped'])->toBe(0);
    expect($results['commands'])->toHaveCount(1);
    expect($results['commands'][0]['status'])->toBe('ran');
    expect($results['commands'][0]['violations'])->toBe(1);
    expect(array_keys($results['commands'][0]))->toBe(['command', 'cwd', 'status', 'output', 'violations']);
    expect($results['commands'][0]['command'])->toBeString()->not->toBeEmpty();
    expect($results['commands'][0]['cwd'])->toBe($project);
    expect($results['commands'][0]['output'])->toBeString();
    expect(file_exists($marker))->toBeTrue();

    @unlink($marker);
    @unlink($project.'/artisan');
    @rmdir($project);
});

it('does not execute command-style fixes during dry run', function () {
    $project = makeCommandFixture($this->tempDir);
    $marker = $this->tempDir.'/dryrun-'.uniqid();

    $violations = [
        new Violation(
            level: 'warning',
            message: 'needs scaffolding',
            file: $project.'/some/blade.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: [PHP_BINARY, '-r', 'touch($argv[1]);', $marker],
            fixCommandCwd: $project,
        ),
    ];

    $results = (new CodeFixer)->fix($violations, dryRun: true);

    expect($results['commands'])->toHaveCount(1);
    expect($results['commands'][0]['status'])->toBe('would-run');
    expect($results['fixed'])->toBe(1);
    expect(file_exists($marker))->toBeFalse();

    @unlink($project.'/artisan');
    @rmdir($project);
});

it('preserves literal arguments in the dry-run command preview', function () {
    $project = makeCommandFixture($this->tempDir);
    $longArgument = str_repeat('x', 32768);
    $violation = new Violation(
        level: 'warning',
        message: 'preview a command',
        file: $project.'/view.blade.php',
        line: 1,
        isFixable: true,
        fixCommand: ['tool', '100%!', 'say "hello"', 'C:\Program Files', '', "first\nsecond", $longArgument],
        fixCommandCwd: $project,
    );

    try {
        $result = (new CodeFixer)->fix([$violation], dryRun: true);

        expect($result['commands'][0]['status'])->toBe('would-run');
        expect($result['commands'][0]['command'])
            ->toBe('"tool" "100%!" "say \"hello\"" "C:\\\\Program Files" "" "first\nsecond" "'.$longArgument.'"');
    } finally {
        unlink($project.'/artisan');
        rmdir($project);
    }
});

it('dedupes identical command-style fixes across multiple violations', function () {
    $project = makeCommandFixture($this->tempDir);
    $counter = $this->tempDir.'/counter-'.uniqid();

    $cmd = [PHP_BINARY, '-r', 'file_put_contents($argv[1], "x", FILE_APPEND);', $counter];

    $violations = [
        new Violation(
            level: 'warning',
            message: 'first',
            file: $project.'/a.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: $cmd,
            fixCommandCwd: $project,
        ),
        new Violation(
            level: 'warning',
            message: 'second',
            file: $project.'/b.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: $cmd,
            fixCommandCwd: $project,
        ),
        new Violation(
            level: 'warning',
            message: 'third',
            file: $project.'/c.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: $cmd,
            fixCommandCwd: $project,
        ),
    ];

    $results = (new CodeFixer)->fix($violations);

    expect($results['commands'])->toHaveCount(1);
    expect($results['commands'][0]['violations'])->toBe(3);
    expect($results['fixed'])->toBe(3);
    expect(file_get_contents($counter))->toBe('x');

    @unlink($counter);
    @unlink($project.'/artisan');
    @rmdir($project);
});

it('marks failed command-style fixes as skipped', function () {
    $project = makeCommandFixture($this->tempDir);

    $violations = [
        new Violation(
            level: 'warning',
            message: 'needs scaffolding',
            file: $project.'/blade.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: [PHP_BINARY, '-r', 'exit(7);'],
            fixCommandCwd: $project,
        ),
    ];

    $results = (new CodeFixer)->fix($violations);

    expect($results['fixed'])->toBe(0);
    expect($results['skipped'])->toBe(1);
    expect($results['commands'])->toHaveCount(1);
    expect($results['commands'][0]['status'])->toBe('failed');
    expect($results['commands'][0]['output'])->toBe('Process exited with code 7.');

    @unlink($project.'/artisan');
    @rmdir($project);
});

it('skips unavailable executables and continues with other commands', function () {
    $project = makeCommandFixture($this->tempDir);
    $violations = [];

    foreach ([[$project.'/missing-executable'], [$project.'/missing-executable'], [PHP_BINARY, '-r', 'echo "ran";']] as $command) {
        $violations[] = new Violation(
            level: 'warning',
            message: 'command execution',
            file: $project.'/blade.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: $command,
            fixCommandCwd: $project,
        );
    }

    try {
        $results = (new CodeFixer)->fix($violations);

        expect($results['fixed'])->toBe(1);
        expect($results['skipped'])->toBe(2);
        expect($results['commands'])->toHaveCount(2);
        expect($results['commands'][0]['status'])->toBe('failed');
        expect($results['commands'][0]['violations'])->toBe(2);
        expect($results['commands'][0]['output'])->toBeString()->not->toBeEmpty();
        expect($results['commands'][1]['status'])->toBe('ran');
        expect($results['commands'][1]['output'])->toBe('ran');
    } finally {
        unlink($project.'/artisan');
        rmdir($project);
    }
});

it('runs identical commands separately in distinct working directories', function () {
    $projects = [makeCommandFixture($this->tempDir), makeCommandFixture($this->tempDir)];
    $violations = [];

    foreach ($projects as $project) {
        $violations[] = new Violation(
            level: 'warning',
            message: 'working directory',
            file: $project.'/blade.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: [PHP_BINARY, '-r', 'echo realpath(".");'],
            fixCommandCwd: $project,
        );
    }

    try {
        $results = (new CodeFixer)->fix($violations);

        expect($results['fixed'])->toBe(2);
        expect($results['skipped'])->toBe(0);
        expect($results['commands'])->toHaveCount(2);
        expect(array_column($results['commands'], 'status'))->toBe(['ran', 'ran']);
        expect(array_column($results['commands'], 'cwd'))->toBe($projects);
        expect(array_column($results['commands'], 'output'))->toBe(array_map('realpath', $projects));
    } finally {
        foreach ($projects as $project) {
            unlink($project.'/artisan');
            rmdir($project);
        }
    }
});

it('refuses to run command-style fixes when cwd has no artisan', function () {
    $bare = $this->tempDir.'/bare-'.uniqid();
    mkdir($bare, 0755, true);
    $marker = $this->tempDir.'/refused-'.uniqid();

    $violations = [
        new Violation(
            level: 'warning',
            message: 'needs scaffolding',
            file: $bare.'/blade.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: [PHP_BINARY, '-r', 'touch($argv[1]);', $marker],
            fixCommandCwd: $bare,
        ),
    ];

    $results = (new CodeFixer)->fix($violations);

    expect($results['fixed'])->toBe(0);
    expect($results['skipped'])->toBe(1);
    expect($results['commands'])->toHaveCount(1);
    expect($results['commands'][0]['status'])->toBe('failed');
    expect($results['commands'][0]['output'])->toContain('artisan');
    expect(file_exists($marker))->toBeFalse();

    @rmdir($bare);
});

it('passes shell metacharacters as literal command arguments', function () {
    $project = makeCommandFixture($this->tempDir);
    $marker = $this->tempDir.'/injected';
    $arguments = [
        'a value with spaces',
        'single\' and double" quotes',
        '; touch '.$marker,
        '$(touch '.$marker.')',
        '`touch '.$marker.'`',
        'a | b & c > d < e * ? $HOME',
        "line one\nline two",
        '',
    ];
    $command = [PHP_BINARY, '-r', 'echo json_encode([realpath("."), array_slice($argv, 1)], JSON_THROW_ON_ERROR);', ...$arguments];

    try {
        $results = (new CodeFixer)->fix([
            new Violation(
                level: 'warning',
                message: 'literal arguments',
                file: $project.'/blade.blade.php',
                line: 1,
                isFixable: true,
                fixCommand: $command,
                fixCommandCwd: $project,
            ),
        ]);

        expect($results['fixed'])->toBe(1);
        expect($results['skipped'])->toBe(0);
        expect($results['commands'][0]['status'])->toBe('ran');
        expect(json_decode($results['commands'][0]['output'], true, flags: JSON_THROW_ON_ERROR))
            ->toBe([realpath($project), $arguments]);
        expect(file_exists($marker))->toBeFalse();
    } finally {
        if (file_exists($marker)) {
            unlink($marker);
        }
        unlink($project.'/artisan');
        rmdir($project);
    }
});

it('keeps distinct argument boundaries when grouping commands', function () {
    $project = makeCommandFixture($this->tempDir);
    $violations = [];

    foreach ([['a b', 'c'], ['a', 'b c']] as $arguments) {
        $violations[] = new Violation(
            level: 'warning',
            message: 'distinct arguments',
            file: $project.'/blade.blade.php',
            line: 1,
            isFixable: true,
            fixCommand: [PHP_BINARY, '-r', 'echo json_encode(array_slice($argv, 1), JSON_THROW_ON_ERROR);', ...$arguments],
            fixCommandCwd: $project,
        );
    }

    try {
        $results = (new CodeFixer)->fix($violations);

        expect($results['fixed'])->toBe(2);
        expect($results['commands'])->toHaveCount(2);
        expect(array_column($results['commands'], 'output'))->toBe(['["a b","c"]', '["a","b c"]']);
    } finally {
        unlink($project.'/artisan');
        rmdir($project);
    }
});

it('rejects shell command strings', function () {
    expect(fn () => new Violation(
        level: 'warning',
        message: 'legacy command',
        file: 'example.blade.php',
        line: 1,
        fixCommand: 'php artisan example',
    ))->toThrow(TypeError::class);
});

it('rejects malformed command argument arrays', function (array $command) {
    expect(fn () => new Violation(
        level: 'warning',
        message: 'invalid command',
        file: 'example.blade.php',
        line: 1,
        fixCommand: $command,
    ))->toThrow(InvalidArgumentException::class);
})->with([
    'empty command' => [[]],
    'empty executable' => [['']],
    'associative arguments' => [['executable' => 'php']],
    'non-string argument' => [['php', 123]],
    'null byte in executable' => [["ph\0p"]],
    'null byte in argument' => [['php', "a\0b"]],
]);

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
