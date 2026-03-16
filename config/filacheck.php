<?php

/**
 * FilaCheck Configuration
 *
 * Set 'enabled' to false to disable a rule entirely.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Deprecated Code Rules
    |--------------------------------------------------------------------------
    */
    'deprecated-reactive' => [
        'enabled' => true,
    ],

    'deprecated-action-form' => [
        'enabled' => true,
    ],

    'deprecated-test-methods' => [
        'enabled' => true,
    ],

    'deprecated-filter-form' => [
        'enabled' => true,
    ],

    'deprecated-placeholder' => [
        'enabled' => true,
    ],

    'deprecated-mutate-form-data-using' => [
        'enabled' => true,
    ],

    'deprecated-empty-label' => [
        'enabled' => true,
    ],

    'deprecated-forms-get' => [
        'enabled' => true,
    ],

    'deprecated-forms-set' => [
        'enabled' => true,
    ],

    'deprecated-image-column-size' => [
        'enabled' => true,
    ],

    'deprecated-view-property' => [
        'enabled' => true,
    ],

    'deprecated-bulk-actions' => [
        'enabled' => true,
    ],

    'deprecated-url-parameters' => [
        'enabled' => true,
    ],

    'deprecated-get-table-query' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Best Practices Rules
    |--------------------------------------------------------------------------
    */
    'action-in-bulk-action-group' => [
        'enabled' => true,
    ],

    'wrong-tab-namespace' => [
        'enabled' => true,
    ],
];
