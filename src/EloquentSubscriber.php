<?php

namespace AlgoliaSearch\Laravel;

class EloquentSubscriber
{
    private $modelHelper;

    public function __construct(ModelHelper $modelHelper)
    {
        $this->modelHelper = $modelHelper;
    }

    public function saved($model)
    {
        if (!$this->modelHelper->isAutoIndex($model)) {
            return true;
        }

        /** @var \AlgoliaSearch\Index $index */
        foreach ($this->modelHelper->getIndices($model) as $index) {
            if ($this->modelHelper->indexOnly($model, $index->indexName)) {
                $index->addObject($this->modelHelper->getAlgoliaRecord($model), $this->modelHelper->getKey($model));
            } else if ($this->modelHelper->deleteOnly($model, $index->indexName)) {
                // model was indexed and currently is not
                $this->deleting($model);
            }
        }

        return true;
    }

    public function deleting($model)
    {
        if (!$this->modelHelper->isAutoDelete($model)) {
            return true;
        }

        /** @var \AlgoliaSearch\Index $index */
        foreach ($this->modelHelper->getIndices($model) as $index) {
            $index->deleteObject($model->id);
        }

        return true;
    }

    public function subscribe($events)
    {
        $events->listen('eloquent.saved*', '\AlgoliaSearch\Laravel\EloquentSubscriber@saved');
        $events->listen('eloquent.deleted*', '\AlgoliaSearch\Laravel\EloquentSubscriber@deleting');
    }
}
