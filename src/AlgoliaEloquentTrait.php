<?php

namespace AlgoliaSearch\Laravel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

trait AlgoliaEloquentTrait
{

    /**
     * Boot the trait by registering the Cache observer with the model
     */
    public static function bootAlgoliaEloquentTrait()
    {
        if (new static instanceof Model) {
            static::observe(App::make('\AlgoliaSearch\Laravel\EloquentSubscriber'));
        } else {
            throw new \Exception("This trait can ony be used in Eloquent models.");
        }
    }

    /**
     * @var string
     */
    private static $methodGetName = 'getAlgoliaRecord';

    /**
     * Static calls.
     *
     * @param bool $safe
     */
    public static function algreindex($safe = true, $batch = 100, $query = '')
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $indices = $modelHelper->getIndices($this);
        $indicesTmp = $safe ? $modelHelper->getIndicesTmp($this) : $indices;

        $staticInstance = new static;
        if ( ! empty($query)) {
            $staticInstance = $staticInstance->whereRaw($query);
        }

        $staticInstance->chunk($batch, function ($models) use ($indicesTmp, $modelHelper) {
            /** @var \AlgoliaSearch\Index $index */
            foreach ($indicesTmp as $index) {
                $records = [];

                foreach ($models as $model) {
                    if ($modelHelper->indexOnly($model, $index->indexName)) {
                        $records[] = $model->getAlgoliaRecordDefault();
                    }
                }

                $index->addObjects($records);
            }

        });

        if ($safe) {
            for ($i = 0; $i < count($indices); $i++) {
                $modelHelper->algolia->moveIndex($indicesTmp[$i]->indexName, $indices[$i]->indexName);
            }
        }
    }

    public static function algclearIndices()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $indices = $modelHelper->getIndices($this);

        /** @var \AlgoliaSearch\Index $index */
        foreach ($indices as $index) {
            $index->clearIndex();
        }
    }

    /**
     * @param $query
     * @param array $parameters
     * @param $cursor
     *
     * @return mixed
     */
    public static function algbrowseFrom($query, $parameters = [], $cursor = null)
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $index = null;

        if (isset($parameters['index'])) {
            $index = $modelHelper->getIndices($this, $parameters['index'])[0];
            unset($parameters['index']);
        } else {
            $index = $modelHelper->getIndices($this)[0];
        }

        $result = $index->browseFrom($query, $parameters, $cursor);

        return $result;
    }

    /**
     * @param $query
     * @param array $parameters
     *
     * @return mixed
     */
    public static function algbrowse($query, $parameters = [])
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $index = null;

        if (isset($parameters['index'])) {
            $index = $modelHelper->getIndices($this, $parameters['index'])[0];
            unset($parameters['index']);
        } else {
            $index = $modelHelper->getIndices($this)[0];
        }

        $result = $index->browse($query, $parameters);

        return $result;
    }

    /**
     * @param $query
     * @param array $parameters
     *
     * @return mixed
     */
    public static function algsearch($query, $parameters = [])
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $index = null;

        if (isset($parameters['index'])) {
            $index = $modelHelper->getIndices($this, $parameters['index'])[0];
            unset($parameters['index']);
        } else {
            $index = $modelHelper->getIndices($this)[0];
        }

        $result = $index->search($query, $parameters);

        return $result;
    }

    public static function algsetSettings()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $settings = $modelHelper->getSettings($this);
        $indices = $modelHelper->getIndices($this);

        $slaves_settings = $modelHelper->getSlavesSettings($this);
        $slaves = isset($settings['slaves']) ? $settings['slaves'] : [];

        $b = true;

        /** @var \AlgoliaSearch\Index $index */
        foreach ($indices as $index) {

            if ($b && isset($settings['slaves'])) {
                $settings['slaves'] = array_map(function ($indexName) use ($modelHelper) {
                    return $modelHelper->getFinalIndexName($this, $indexName);
                }, $settings['slaves']);
            }

            if (count(array_keys($settings)) > 0) {
                $index->setSettings($settings);
            }

            if ($b && isset($settings['slaves'])) {
                $b = false;
                unset($settings['slaves']);
            }
        }

        foreach ($slaves as $slave) {
            if (isset($slaves_settings[$slave])) {
                $index = $modelHelper->getIndices($this, $slave)[0];

                $s = array_merge($settings, $slaves_settings[$slave]);

                if (count(array_keys($s)) > 0)
                    $index->setSettings($s);
            }
        }
    }

    /**
     * Methods.
     */
    public function getAlgoliaRecordDefault()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $record = null;

        if (method_exists($this, static::$methodGetName)) {
            $record = $this->{static::$methodGetName}();
        } else {
            $record = $this->toArray();
        }

        if (isset($record['objectID']) == false) {
            $record['objectID'] = $modelHelper->getObjectId($this);
        }

        return $record;
    }

    public function pushToIndex()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $indices = $modelHelper->getIndices($this);

        /** @var \AlgoliaSearch\Index $index */
        foreach ($indices as $index) {
            if ($modelHelper->indexOnly($this, $index->indexName)) {
                $index->addObject($this->getAlgoliaRecordDefault());
            }
        }
    }

    public function removeFromIndex()
    {
        /** @var \AlgoliaSearch\Laravel\ModelHelper $modelHelper */
        $modelHelper = App::make('\AlgoliaSearch\Laravel\ModelHelper');

        $indices = $modelHelper->getIndices($this);

        /** @var \AlgoliaSearch\Index $index */
        foreach ($indices as $index) {
            $index->deleteObject($modelHelper->getObjectId($this));
        }
    }
}
