<?php

namespace Filacheck;

use Filacheck\Rules\ActionInBulkActionGroupRule;
use Filacheck\Rules\DeprecatedActionAssertsRule;
use Filacheck\Rules\DeprecatedActionFormRule;
use Filacheck\Rules\DeprecatedBulkActionsRule;
use Filacheck\Rules\DeprecatedEmptyLabelRule;
use Filacheck\Rules\DeprecatedFilterFormRule;
use Filacheck\Rules\DeprecatedFormAssertsRule;
use Filacheck\Rules\DeprecatedFormsGetRule;
use Filacheck\Rules\DeprecatedFormsSetRule;
use Filacheck\Rules\DeprecatedImageColumnSizeRule;
use Filacheck\Rules\DeprecatedInfolistAssertsRule;
use Filacheck\Rules\DeprecatedMutateFormDataUsingRule;
use Filacheck\Rules\DeprecatedPlaceholderRule;
use Filacheck\Rules\DeprecatedReactiveRule;
use Filacheck\Rules\DeprecatedTableAssertsRule;
use Filacheck\Rules\DeprecatedUrlParametersRule;
use Filacheck\Rules\DeprecatedViewPropertyRule;
use Filacheck\Rules\Rule;
use Filacheck\Rules\WrongTabNamespaceRule;
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

    /** @return array<class-string<Rule>> */
    public static function rules(): array
    {
        return [
            DeprecatedReactiveRule::class,
            DeprecatedActionFormRule::class,
            DeprecatedActionAssertsRule::class,
            DeprecatedTableAssertsRule::class,
            DeprecatedFormAssertsRule::class,
            DeprecatedInfolistAssertsRule::class,
            DeprecatedFilterFormRule::class,
            DeprecatedPlaceholderRule::class,
            DeprecatedMutateFormDataUsingRule::class,
            DeprecatedEmptyLabelRule::class,
            DeprecatedFormsGetRule::class,
            DeprecatedFormsSetRule::class,
            DeprecatedImageColumnSizeRule::class,
            DeprecatedViewPropertyRule::class,
            ActionInBulkActionGroupRule::class,
            DeprecatedBulkActionsRule::class,
            WrongTabNamespaceRule::class,
            DeprecatedUrlParametersRule::class,
        ];
    }
}
