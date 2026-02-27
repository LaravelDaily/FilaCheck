<?php

use Filacheck\Rules\DeprecatedReactiveRule;
use Filacheck\Scanner\ResourceScanner;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/filacheck-scanner-test-' . uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    $files = glob($this->tempDir . '/*');
    foreach ($files as $file) {
        unlink($file);
    }
    rmdir($this->tempDir);
});

it('scans a single php file path', function () {
    $file = $this->tempDir . '/OrderForm.php';
    file_put_contents($file, '<?php $input->reactive();');

    $scanner = new ResourceScanner;
    $scanner->addRule(new DeprecatedReactiveRule);

    $violations = $scanner->scan($file, $this->tempDir);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->file)->toBe('OrderForm.php');
});

it('does not scan sibling files when a single file path is provided', function () {
    $targetFile = $this->tempDir . '/OrderForm.php';
    $otherFile = $this->tempDir . '/AnotherForm.php';

    file_put_contents($targetFile, '<?php $input->reactive();');
    file_put_contents($otherFile, '<?php $input->reactive();');

    $scanner = new ResourceScanner;
    $scanner->addRule(new DeprecatedReactiveRule);

    $violations = $scanner->scan($targetFile, $this->tempDir);

    expect($violations)->toHaveCount(1);
    expect($violations[0]->file)->toBe('OrderForm.php');
});
