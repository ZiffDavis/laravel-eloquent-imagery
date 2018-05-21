<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReflectionProperty;

class EloquentImageryObserver
{
    protected $attributeReflector = null;

    public function __construct()
    {
        $this->attributeReflector = new ReflectionProperty(Model::class, 'attributes');
        $this->attributeReflector->setAccessible(true);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function retrieved(Model $model)
    {
        $attributeImages = $model->getEloquentImageryImages();

        $modelAttributes = $this->attributeReflector->getValue($model);

        foreach ($attributeImages as $attribute => $image) {
            $properties = $modelAttributes[$attribute];
            $modelAttributes[$attribute] = $image;

            if ($properties == '') {
                continue;
            }

            if (is_string($properties)) {
                $properties = json_decode($properties, true);
            }

            $image->setStateProperties($properties);
        }

        $this->attributeReflector->setValue($model, $modelAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function saving(Model $model)
    {
        $attributeImages = $model->getEloquentImageryImages();

        $casts = $model->getCasts();

        $modelAttributes = $this->attributeReflector->getValue($model);

        foreach ($attributeImages as $attribute => $image) {
            if ($image->pathHasReplacements()) {
                $image->updatePath($model);
            }

            if ($image instanceof ImageCollection) {
                $image->purgeRemovedImages();
            }

            if (!$image->exists()) {
                $modelAttributes[$attribute] = null;
                continue;
            }

            $imageState = $image->getStateProperties();

            $value = (isset($casts[$attribute]) && $casts[$attribute] === 'json')
                ? $imageState
                : json_encode($imageState);

            $modelAttributes[$attribute] = $value;
        }

        $this->attributeReflector->setValue($model, $modelAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function saved(Model $model)
    {
        $attributeImages = $model->getEloquentImageryImages();

        $errors = [];

        $modelAttributes = $this->attributeReflector->getValue($model);

        foreach ($attributeImages as $attribute => $image) {
            if ($image->pathHasReplacements()) {

                $image->updatePath($model);

                if ($image->pathHasReplacements()) {
                    $errors[] = "After saving row, image for attribute {$attribute}'s path still contains unresolvable path replacements";
                }

                $imageState = $image->getStateProperties();

                $value = (isset($this->casts[$attribute]) && $this->casts[$attribute] === 'json')
                    ? $imageState
                    : json_encode($imageState);

                $model->getConnection()
                    ->table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->update([$attribute => $value]);
            }
            $image->flush();

            $modelAttributes[$attribute] = $image;
        }

        $this->attributeReflector->setValue($model, $modelAttributes);

        if ($errors) {
            throw new \RuntimeException(implode('; ', $errors));
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function deleted(Model $model)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && !$model->isForceDeleting()) {
            return;
        }

        foreach ($model->getEloquentImageryImages() as $image) {
            if ($image->exists()) {
                $image->remove();
                $image->flush();
            }
        }
    }
}
