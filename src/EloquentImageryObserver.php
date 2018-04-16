<?php

namespace ZiffDavis\Laravel\EloquentImagery;

use Illuminate\Database\Eloquent\Model;

class EloquentImageryObserver
{
    public function saving(Model $model)
    {
        foreach ($model->getEloquentImageryImages() as $imageAttributeKey => $image) {
            $image->serializeToModel();
        }
    }

    public function saved(Model $model)
    {
        foreach ($model->getEloquentImageryImages() as $imageAttributeKey => $image) {
            if ($image->pathHasReplacements()) {
                $image->updatePath();

                if ($image->pathHasReplacements()) {
                    throw new \RuntimeException('After saving row, image path still contains unresolvable path replacements');
                }

                $value = $image->getSerializedAttributeValue();

                $model->getConnection()
                    ->table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->update([$imageAttributeKey => $value]);
            }
            $image->flush();
        }
    }

    public function deleting(Model $model)
    {
        $methods = get_class_methods($model);
        foreach ($methods as $method) {
            if (preg_match('#^get(\w+)Attribute$#', $method)) {
                $return = $model->{$method}();
                if ($return instanceof Image) {
                    $return->removeOnFlush();
                }
            }
        }
    }

    public function deleted(Model $model)
    {
        foreach ($model->hydratedImages as $imageAttributeKey => $image) {
            if ($image instanceof Image) {
                $model->imageUpdatePath($image);
                $image->flush();
            }
        }
    }
}