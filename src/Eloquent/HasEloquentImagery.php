<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasEloquentImagery
{
    /** @var Image[] */
    protected static $eloquentImageryPrototypes = [];

    /** @var Image[] */
    protected $eloquentImageryImages = [];

    public static function bootHasEloquentImagery()
    {
        static::observe(new EloquentImageryObserver());
    }

    public function eloquentImagery($attribute, $path = null, $filesystem = null)
    {
        if (!isset(static::$eloquentImageryPrototypes[$attribute])) {
            if (!$path) {
                $path = Str::singular($this->getTable()) . '/{' . $this->getKeyName() . "}/{$attribute}.{extension}";
            }

            $filesystem = $filesystem ?? config('eloquent_imagery.filesystem', config('filesystems.default'));
            static::$eloquentImageryPrototypes[$attribute] = new Image($attribute, $path, $filesystem);
        }

        $image = clone static::$eloquentImageryPrototypes[$attribute];

        // set the image as the attribute so that it cavn be accessed on new instances via attribute accessor
        $this->eloquentImageryImages[$attribute] = $this->attributes[$attribute] = $image;

        $this->attributes[$attribute]->setModel($this);
    }

    public function eloquentImagerySerialize()
    {
        foreach ($this->eloquentImageryImages as $attribute => $image) {
            $this->attributes[$attribute] = $image->getSerializedAttributeValue();
        }
    }



    public function eloquentImageryRestoreImagesToAttributes()
    {
        foreach ($this->eloquentImageryImages as $attribute => $image) {
            $this->attributes[$attribute] = $image;
        }
    }

    public function eloquentImageryCollection()
    {
        // @todo attribute as a collection of images
    }

    /**
     * @return \ZiffDavis\Laravel\EloquentImagery\Eloquent\Image[]
     */
    public function getEloquentImageryImages()
    {
        return $this->eloquentImageryImages;
    }
}
