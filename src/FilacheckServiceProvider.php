<?php

namespace Filacheck;

use Filacheck\Rules\ActionInBulkActionGroupRule;
use Filacheck\Rules\DeprecatedActionFormRule;
use Filacheck\Rules\DeprecatedEmptyLabelRule;
use Filacheck\Rules\DeprecatedFilterFormRule;
use Filacheck\Rules\DeprecatedFormsSetRule;
use Filacheck\Rules\DeprecatedImageColumnSizeRule;
use Filacheck\Rules\DeprecatedMutateFormDataUsingRule;
use Filacheck\Rules\DeprecatedPlaceholderRule;
use Filacheck\Rules\DeprecatedReactiveRule;
use Filacheck\Rules\DeprecatedViewPropertyRule;
use Filacheck\Support\RuleRegistry;
use Illuminate\Support\ServiceProvider;

class FilacheckServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RuleRegistry::class);
    }

    public function boot(): void
    {
        $this->app->make(RuleRegistry::class)->register(static::rules());
    }

    /** @return array<class-string<\Filacheck\Rules\Rule>> */
    public static function rules(): array
    {
        return [
            DeprecatedReactiveRule::class,
            DeprecatedActionFormRule::class,
            DeprecatedFilterFormRule::class,
            DeprecatedPlaceholderRule::class,
            DeprecatedMutateFormDataUsingRule::class,
            DeprecatedEmptyLabelRule::class,
            DeprecatedFormsSetRule::class,
            DeprecatedImageColumnSizeRule::class,
            DeprecatedViewPropertyRule::class,
            ActionInBulkActionGroupRule::class,
        ];
    }
}
