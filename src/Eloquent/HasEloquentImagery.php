<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasEloquentImagery
{
    protected static $eloquentImageryRuntime = [
        'observer'                => null,
        'imagery_enabled_classes' => [] // keys: prototype, config
    ];

    /** @var Image[] */
    protected static $eloquentImageryPrototypes = [];

    /** @var Image[]|ImageCollection[] */
    protected $eloquentImageryImages = [];

    /**
     * Class level (Per-Model) initialization (booting) of the eloquent model, this happens
     * once per request/php-exection
     */
    public static function bootHasEloquentImagery()
    {
        static::observe(new EloquentImageryObserver());

        // todo, see if there is an opportunity to do config cleaning, prototyping here
    }

    /**
     * Per-instance level initialation, this happens every time a Model is new'd up
     */
    public function initializeHasEloquentImagery()
    {
        if (!empty($this->eloquentImageryImages)) {
            throw new \RuntimeException('$eloquentImageryImages should be empty, are you sure you have your configuration in the right place?');
        }

        if (empty($this->eloquentImagery) || !property_exists($this, 'eloquentImagery')) {
            throw new \RuntimeException('You are using ' . __TRAIT__ . ' but have not yet configured it through $eloquentImagery, please see the docs');
        }

        foreach ($this->eloquentImagery as $attribute => $config) {
            if (is_string($config)) {
                $config = ['path' => $config];
            }

            if (isset($config['collection']) && $config['collection'] === true) {
                $this->eloquentImageryCollection($attribute, $config['path']);
            } else {
                $this->eloquentImagery($attribute, $config['path']);
            }
        }
    }

    public function eloquentImagery($attribute, $path = null)
    {
        $this->eloquentImageryInitializeImage(Image::class, $attribute, $path);
    }

    public function eloquentImageryCollection($attribute, $path = null)
    {
        $this->eloquentImageryInitializeImage(ImageCollection::class, $attribute, $path);
    }

    protected function eloquentImageryInitializeImage($class, $attribute, $path)
    {
        if (!isset(static::$eloquentImageryPrototypes[$attribute])) {
            if (!$path) {
                $path = ($class === ImageCollection::class)
                    ? Str::singular($this->getTable()) . '/{' . $this->getKeyName() . "}/{$attribute}-{index}.{extension}"
                    : Str::singular($this->getTable()) . '/{' . $this->getKeyName() . "}/{$attribute}.{extension}";
            }

            static::$eloquentImageryPrototypes[$attribute] = new $class(
                app(FilesystemManager::class)->disk(config('eloquent-imagery.filesystem', config('filesystems.default')))
            );
        }

        // set the image as the attribute so that it can be accessed on new instances via attribute accessor
        $this->eloquentImageryImages[$attribute] = $this->attributes[$attribute] = clone static::$eloquentImageryPrototypes[$attribute];
    }

    /**
     * @return Image[]|ImageCollection[]
     */
    public function getEloquentImageryImages()
    {
        return $this->eloquentImageryImages;
    }
}
