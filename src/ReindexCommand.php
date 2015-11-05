<?php

namespace AlgoliaSearch\Laravel;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;

class ReindexCommand extends Command
{
    /**
    * The console command name.
    *
    * @var string
    */
    protected $name = 'algolia:reindex';

    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Reindex Eloquent models to AlgoliaSearch.';

    /**
    * Execute the console command.
    *
    * @return mixed
    */
    public function fire()
    {
        $directoryModels = [];
        $models = $this->argument('model');

        foreach ($models as $model)
        {
            $instance = $this->getModelInstance($model);
            $this->reindexModel($instance);
        }

        if (empty($models))
        {
            $this->info('No models found.');
        }
    }

    /**
    * Get the console command arguments.
    *
    * @return array
    */
    protected function getArguments()
    {
        return array(
            array('model', InputOption::VALUE_OPTIONAL, 'Eloquent model to reindex', null)
        );
    }

    /**
    * Get the console command options.
    *
    * @return array
    */
    protected function getOptions()
    {
        return array(
            array('query', null, InputOption::VALUE_OPTIONAL, 'Reindex related Eloquent models', null),
            array('batch', null, InputOption::VALUE_OPTIONAL, 'The number of records to index in a single batch', 100),
        );
    }

    /**
    * Reindex a model to Elasticsearch
    *
    * @param Model $model
    */
    protected function reindexModel(Model $model)
    {
        $this->info('---> Reindexing ' . get_class($model));

        $model->algreindex(true, $this->option('batch'), $this->option('query'));
    }

    /**
    * Simple method to create instances of classes on the fly
    * It's primarily here to enable unit-testing
    *
    * @param string $model
    */
    protected function getModelInstance($model)
    {
        return new $model;
    }
}
