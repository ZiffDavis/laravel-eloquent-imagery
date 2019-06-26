<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReflectionProperty;

class EloquentImageryObserver
{
    /** @var ReflectionProperty  */
    protected $eloquentImageryImagesReflector;

    /** @var ReflectionProperty */
    protected $attributesReflector;

    public function __construct($modelClassToObserve)
    {
        $this->eloquentImageryImagesReflector = new ReflectionProperty($modelClassToObserve, 'eloquentImageryImages');
        $this->eloquentImageryImagesReflector->setAccessible(true);

        $this->attributesReflector = new ReflectionProperty($modelClassToObserve, 'attributes');
        $this->attributesReflector->setAccessible(true);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function retrieved(Model $model)
    {
        $eloquentImageryImages = $this->eloquentImageryImagesReflector->getValue($model);

        $modelAttributes = $this->attributesReflector->getValue($model);

        foreach ($eloquentImageryImages as $attribute => $image) {
            // in the case a model was retrieved and the image column was not returned
            if (!array_key_exists($attribute, $modelAttributes)) {
                continue;
            }

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

        $this->attributesReflector->setValue($model, $modelAttributes);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery $model
     */
    public function saving(Model $model)
    {
        $eloquentImageryImages = $this->eloquentImageryImagesReflector->getValue($model);

        $casts = $model->getCasts();

        $modelAttributes = $this->attributeReflector->getValue($model);

        foreach ($eloquentImageryImages as $attribute => $image) {
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
        $eloquentImageryImages = $this->eloquentImageryImagesReflector->getValue($model);

        $errors = [];

        $modelAttributes = $this->attributeReflector->getValue($model);

        foreach ($eloquentImageryImages as $attribute => $image) {
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
