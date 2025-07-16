<?php

namespace Luminee\Belobog;

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

        // $this->commands([
        //     MakeMigrationCommand::class,
        //     MigrateCommand::class,
        // ]);
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
