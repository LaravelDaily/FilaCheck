<?php

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
        \Filacheck\Rules\DeprecatedActionFormRule::class,
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
