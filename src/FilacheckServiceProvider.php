<?php

namespace Filacheck;

use Filacheck\Rules\ActionInBulkActionGroupRule;
use Filacheck\Rules\DeprecatedActionFormRule;
use Filacheck\Rules\DeprecatedBulkActionsRule;
use Filacheck\Rules\DeprecatedEmptyLabelRule;
use Filacheck\Rules\DeprecatedFilterFormRule;
use Filacheck\Rules\DeprecatedFormsGetRule;
use Filacheck\Rules\DeprecatedFormsSetRule;
use Filacheck\Rules\DeprecatedGetTableQueryRule;
use Filacheck\Rules\DeprecatedImageColumnSizeRule;
use Filacheck\Rules\DeprecatedMutateFormDataUsingRule;
use Filacheck\Rules\DeprecatedPlaceholderRule;
use Filacheck\Rules\DeprecatedReactiveRule;
use Filacheck\Rules\DeprecatedTestMethodsRule;
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
        $this->mergeConfigFrom(__DIR__.'/../config/filacheck.php', 'filacheck');

        $this->publishes([
            __DIR__.'/../config/filacheck.php' => config_path('filacheck.php'),
        ], 'filacheck-config');

        $rules = array_filter(static::rules(), function (string $ruleClass): bool {
            $name = (new $ruleClass)->name();
            try {
                return (bool) config("filacheck.{$name}.enabled", true);
            } catch (\Throwable) {
                return true;
            }
        });

        $this->app->make(RuleRegistry::class)->register(array_values($rules));
    }

    /** @return array<class-string<Rule>> */
    public static function rules(): array
    {
        return [
            DeprecatedReactiveRule::class,
            DeprecatedActionFormRule::class,
            DeprecatedTestMethodsRule::class,
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
            DeprecatedGetTableQueryRule::class,
        ];
    }
}
