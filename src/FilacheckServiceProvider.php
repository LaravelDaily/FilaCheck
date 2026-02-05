<?php

namespace Filacheck;

use Filacheck\Commands\FilacheckCommand;
use Filacheck\Rules\DeprecatedActionFormRule;
use Filacheck\Rules\DeprecatedEmptyLabelRule;
use Filacheck\Rules\DeprecatedFilterFormRule;
use Filacheck\Rules\DeprecatedFormsSetRule;
use Filacheck\Rules\DeprecatedMutateFormDataUsingRule;
use Filacheck\Rules\DeprecatedPlaceholderRule;
use Filacheck\Rules\DeprecatedReactiveRule;
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
        $this->registerFreeRules();

        if ($this->app->runningInConsole()) {
            $this->commands([
                FilacheckCommand::class,
            ]);
        }
    }

    protected function registerFreeRules(): void
    {
        $this->app->make(RuleRegistry::class)->register([
            DeprecatedReactiveRule::class,
            DeprecatedActionFormRule::class,
            DeprecatedFilterFormRule::class,
            DeprecatedPlaceholderRule::class,
            DeprecatedMutateFormDataUsingRule::class,
            DeprecatedEmptyLabelRule::class,
            DeprecatedFormsSetRule::class,
        ]);
    }
}
