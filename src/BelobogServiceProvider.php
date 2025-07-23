<?php

namespace Luminee\Belobog;

use Luminee\Belobog\Console\Commands\MakeMigrationCommand;
use Luminee\Belobog\Console\Commands\MakeSeederCommand;
use Luminee\Belobog\Console\Commands\MigrateCommand;
use Luminee\Foundry\Contracts\ServiceProvider;

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
            MakeSeederCommand::class,
            MigrateCommand::class,
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
