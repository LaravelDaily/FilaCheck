<?php

namespace Filacheck\Tests\Boost;

use Illuminate\Support\Facades\Blade;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Boost\Install\GuidelineAssist;
use Laravel\Boost\Install\GuidelineConfig;
use Laravel\Roster\Roster;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class BoostTestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BoostServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['env'] = 'local';
    }

    protected function renderGuideline(bool $usesSail): string
    {
        $config = new GuidelineConfig;
        $config->usesSail = $usesSail;

        $roster = \Mockery::mock(Roster::class);
        $assist = new GuidelineAssist($roster, $config);

        $template = file_get_contents(
            realpath(__DIR__.'/../../resources/boost/guidelines/core.blade.php')
        );

        return Blade::render($template, ['assist' => $assist]);
    }
}
