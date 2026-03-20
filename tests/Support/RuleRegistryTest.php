<?php

use Filacheck\FilacheckServiceProvider;
use Filacheck\Rules\DeprecatedActionFormRule;
use Filacheck\Rules\DeprecatedReactiveRule;
use Filacheck\Support\RuleRegistry;

it('registers a single rule', function () {
    $registry = new RuleRegistry;

    $registry->register(DeprecatedReactiveRule::class);

    expect($registry->count())->toBe(1);
    expect($registry->has(DeprecatedReactiveRule::class))->toBeTrue();
});

it('registers multiple rules', function () {
    $registry = new RuleRegistry;

    $registry->register([
        DeprecatedReactiveRule::class,
        DeprecatedActionFormRule::class,
    ]);

    expect($registry->count())->toBe(2);
});

it('prevents duplicate registrations', function () {
    $registry = new RuleRegistry;

    $registry->register(DeprecatedReactiveRule::class);
    $registry->register(DeprecatedReactiveRule::class);

    expect($registry->count())->toBe(1);
});

it('returns all registered rules', function () {
    $registry = new RuleRegistry;

    $registry->register(DeprecatedReactiveRule::class);

    $rules = $registry->all();

    expect($rules)->toHaveCount(1);
    expect($rules[0])->toBeInstanceOf(DeprecatedReactiveRule::class);
});

it('supports chained registration', function () {
    $registry = new RuleRegistry;

    $result = $registry->register(DeprecatedReactiveRule::class);

    expect($result)->toBeInstanceOf(RuleRegistry::class);
});

it('registers all rules from FilacheckServiceProvider', function () {
    $rules = FilacheckServiceProvider::rules();

    expect($rules)
        ->toBeArray()
        ->toEqual([
            \Filacheck\Rules\DeprecatedReactiveRule::class,
            \Filacheck\Rules\DeprecatedActionFormRule::class,
            \Filacheck\Rules\DeprecatedTestMethodsRule::class,
            \Filacheck\Rules\DeprecatedFilterFormRule::class,
            \Filacheck\Rules\DeprecatedPlaceholderRule::class,
            \Filacheck\Rules\DeprecatedMutateFormDataUsingRule::class,
            \Filacheck\Rules\DeprecatedEmptyLabelRule::class,
            \Filacheck\Rules\DeprecatedFormsGetRule::class,
            \Filacheck\Rules\DeprecatedFormsSetRule::class,
            \Filacheck\Rules\DeprecatedImageColumnSizeRule::class,
            \Filacheck\Rules\DeprecatedViewPropertyRule::class,
            \Filacheck\Rules\ActionInBulkActionGroupRule::class,
            \Filacheck\Rules\DeprecatedBulkActionsRule::class,
            \Filacheck\Rules\WrongTabNamespaceRule::class,
            \Filacheck\Rules\DeprecatedUrlParametersRule::class,
            \Filacheck\Rules\DeprecatedGetTableQueryRule::class,
    ]);
});

it('cliRules returns all rules when no config file exists', function () {
    $originalDir = getcwd();
    $tempDir = sys_get_temp_dir().'/filacheck-test-'.uniqid();
    mkdir($tempDir);

    try {
        chdir($tempDir);
        $rules = FilacheckServiceProvider::cliRules();

        expect($rules)->toHaveCount(count(FilacheckServiceProvider::rules()));
    } finally {
        chdir($originalDir);
        rmdir($tempDir);
    }
});

it('cliRules excludes rules disabled in config', function () {
    $originalDir = getcwd();
    $tempDir = sys_get_temp_dir().'/filacheck-test-'.uniqid();
    mkdir($tempDir.'/config', 0755, true);

    file_put_contents($tempDir.'/config/filacheck.php', "<?php\nreturn [\n    'deprecated-reactive' => ['enabled' => false],\n    'deprecated-action-form' => ['enabled' => false],\n];\n");

    try {
        chdir($tempDir);
        $rules = FilacheckServiceProvider::cliRules();

        expect($rules)
            ->not->toContain(\Filacheck\Rules\DeprecatedReactiveRule::class)
            ->not->toContain(\Filacheck\Rules\DeprecatedActionFormRule::class)
            ->toHaveCount(count(FilacheckServiceProvider::rules()) - 2);
    } finally {
        chdir($originalDir);
        unlink($tempDir.'/config/filacheck.php');
        rmdir($tempDir.'/config');
        rmdir($tempDir);
    }
});

it('cliRules keeps rules that are explicitly enabled in config', function () {
    $originalDir = getcwd();
    $tempDir = sys_get_temp_dir().'/filacheck-test-'.uniqid();
    mkdir($tempDir.'/config', 0755, true);

    file_put_contents($tempDir.'/config/filacheck.php', "<?php\nreturn [\n    'deprecated-reactive' => ['enabled' => true],\n];\n");

    try {
        chdir($tempDir);
        $rules = FilacheckServiceProvider::cliRules();

        expect($rules)
            ->toContain(\Filacheck\Rules\DeprecatedReactiveRule::class)
            ->toHaveCount(count(FilacheckServiceProvider::rules()));
    } finally {
        chdir($originalDir);
        unlink($tempDir.'/config/filacheck.php');
        rmdir($tempDir.'/config');
        rmdir($tempDir);
    }
});
