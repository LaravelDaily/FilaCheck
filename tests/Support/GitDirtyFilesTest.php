<?php

use Filacheck\Support\GitDirtyFiles;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/filacheck-git-dirty-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
    exec('git init '.$this->tempDir);
    exec('cd '.$this->tempDir.' && git config user.email "test@test.com" && git config user.name "Test"');
});

afterEach(function () {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tempDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($this->tempDir);
});

it('returns null when not in a git repository', function () {
    $nonGitDir = sys_get_temp_dir().'/filacheck-no-git-test-'.uniqid();
    mkdir($nonGitDir, 0755, true);

    $result = GitDirtyFiles::get($nonGitDir);

    rmdir($nonGitDir);

    expect($result)->toBeNull();
});

it('returns empty array when there are no dirty files', function () {
    file_put_contents($this->tempDir.'/file.php', '<?php echo "hello";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toBe([]);
});

it('returns modified files', function () {
    file_put_contents($this->tempDir.'/file.php', '<?php echo "hello";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    file_put_contents($this->tempDir.'/file.php', '<?php echo "changed";');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toHaveCount(1);
    expect($result[0])->toEndWith('/file.php');
});

it('returns untracked files', function () {
    file_put_contents($this->tempDir.'/existing.php', '<?php echo "hello";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    file_put_contents($this->tempDir.'/new-file.php', '<?php echo "new";');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toHaveCount(1);
    expect($result[0])->toEndWith('/new-file.php');
});

it('returns staged files', function () {
    file_put_contents($this->tempDir.'/file.php', '<?php echo "hello";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    file_put_contents($this->tempDir.'/file.php', '<?php echo "staged";');
    exec('cd '.$this->tempDir.' && git add .');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toHaveCount(1);
    expect($result[0])->toEndWith('/file.php');
});

it('excludes deleted files', function () {
    file_put_contents($this->tempDir.'/keep.php', '<?php echo "keep";');
    file_put_contents($this->tempDir.'/delete.php', '<?php echo "delete";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    unlink($this->tempDir.'/delete.php');
    file_put_contents($this->tempDir.'/keep.php', '<?php echo "changed";');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toHaveCount(1);
    expect($result[0])->toEndWith('/keep.php');
});

it('returns absolute file paths', function () {
    file_put_contents($this->tempDir.'/file.php', '<?php echo "hello";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    file_put_contents($this->tempDir.'/file.php', '<?php echo "changed";');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toHaveCount(1);
    expect($result[0])->toStartWith('/');
    expect(file_exists($result[0]))->toBeTrue();
    // Paths should be resolved (no symlinks) since realpath() is used
    expect($result[0])->toBe(realpath($this->tempDir.'/file.php'));
});

it('returns files from subdirectories', function () {
    $subDir = $this->tempDir.'/app/Filament';
    mkdir($subDir, 0755, true);
    file_put_contents($subDir.'/Resource.php', '<?php echo "hello";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    file_put_contents($subDir.'/Resource.php', '<?php echo "changed";');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toHaveCount(1);
    expect($result[0])->toEndWith('/app/Filament/Resource.php');
});

it('returns multiple dirty files', function () {
    file_put_contents($this->tempDir.'/a.php', '<?php echo "a";');
    file_put_contents($this->tempDir.'/b.php', '<?php echo "b";');
    exec('cd '.$this->tempDir.' && git add . && git commit -m "initial"');

    file_put_contents($this->tempDir.'/a.php', '<?php echo "changed a";');
    file_put_contents($this->tempDir.'/b.php', '<?php echo "changed b";');
    file_put_contents($this->tempDir.'/c.php', '<?php echo "new c";');

    $result = GitDirtyFiles::get($this->tempDir);

    expect($result)->toHaveCount(3);
});
