<?php

namespace Luminee\Belobog;

use Luminee\Belobog\Console\Commands\MakeMigrationCommand;
use Luminee\Belobog\Console\Commands\MakeScriptCommand;
use Luminee\Belobog\Console\Commands\MakeSeederCommand;
use Luminee\Belobog\Console\Commands\MigrateCommand;
use Luminee\Belobog\Console\Commands\SeedCommand;
use Luminee\Foundry\Abstracts\ServiceProvider;

class BelobogServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();

        $this->commands([
            MakeMigrationCommand::class,
            MakeScriptCommand::class,
            MakeSeederCommand::class,
            MigrateCommand::class,
            SeedCommand::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerVendor(__DIR__ . '/../', 'belobog');

        $this->mergeConfig();

        $this->getStubs();
    }
}
