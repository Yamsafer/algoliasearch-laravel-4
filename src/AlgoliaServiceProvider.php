<?php

namespace AlgoliaSearch\Laravel;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AlgoliaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerCommands();
    }

    /**
    * Register the commands.
    *
    * @return void
    */
    protected function registerCommands()
    {
        $this->app['algoliasearch.laravel.reindex'] = $this->app->share(function ($app)
        {
            return new ReindexCommand();
        });

        $this->commands('algoliasearch.laravel.reindex');
    }
}
