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
        $this->eloquentImageryInitializeImage(Image::class, $attribute, $path, $filesystem);
    }

    public function eloquentImageryCollection($attribute, $path = null, $filesystem = null)
    {
        $this->eloquentImageryInitializeImage(ImageCollection::class, $attribute, $path, $filesystem);
    }

    protected function eloquentImageryInitializeImage($class, $attribute, $path, $filesystem)
    {
        if (!isset(static::$eloquentImageryPrototypes[$attribute])) {
            if (!$path) {
                $path = ($class === ImageCollection::class)
                    ? Str::singular($this->getTable()) . '/{' . $this->getKeyName() . "}/{$attribute}-{index}.{extension}"
                    : Str::singular($this->getTable()) . '/{' . $this->getKeyName() . "}/{$attribute}.{extension}";
            }

            if (!$filesystem) {
                $filesystem = config('eloquent_imagery.filesystem', config('filesystems.default'));
            }

            static::$eloquentImageryPrototypes[$attribute] = new $class($attribute, $path, $filesystem);
        }

        // set the image as the attribute so that it can be accessed on new instances via attribute accessor
        $this->eloquentImageryImages[$attribute] = $this->attributes[$attribute] = clone static::$eloquentImageryPrototypes[$attribute];
    }

    /**
     * @return \ZiffDavis\Laravel\EloquentImagery\Eloquent\Image[]
     */
    public function getEloquentImageryImages()
    {
        return $this->eloquentImageryImages;
    }
}
