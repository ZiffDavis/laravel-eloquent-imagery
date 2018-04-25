<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentImageryObserver
{
    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function retrieved(Model $model)
    {
        foreach ($model->getEloquentImageryImages() as $imageAttributeKey => $image) {
            $image->unserializeFromModel();
        }
        $model->eloquentImageryRestoreImagesToAttributes();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function saving(Model $model)
    {
        $model->eloquentImagerySerialize();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
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

        $model->eloquentImageryRestoreImagesToAttributes();
    }

    public function deleted(Model $model)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && !$model->isForceDeleting()) {
            return;
        }

        foreach ($model->getEloquentImageryImages() as $image) {
            $image->removeOnFlush();
            $image->flush();
        }
    }
}