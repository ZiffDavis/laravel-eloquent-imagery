<?php

namespace ZiffDavis\Laravel\EloquentImagery;

use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasEloquentImagery
{
    /** @var Image[] */
    protected $eloquentImageryImages = [];

    public static function bootHasEloquentImagery()
    {
        static::observe(new EloquentImageryObserver());
    }

    /**
     * @param null $attribute
     * @param null $path
     * @param null $filesystem
     * @return Image
     */
    public function eloquentImagery($path, $attribute = null, $filesystem = null)
    {
        if (!$attribute) {
            $attribute = Str::snake(
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function']
            );
        }

        if (!isset($this->eloquentImageryImages[$attribute])) {
            $filesystem = $filesystem ?? config('eloquent_imagery.filesystem', config('filesystems.default'));
            $this->eloquentImageryImages[$attribute] = $image = new Image($this, $attribute, $path, $filesystem);
        }

        return $this->eloquentImageryImages[$attribute];
    }

    public function eloquentImageryCollection()
    {
        // @todo attribute as a collection of images
    }

    public function getEloquentImageryImages()
    {
        return $this->eloquentImageryImages;
    }
}
