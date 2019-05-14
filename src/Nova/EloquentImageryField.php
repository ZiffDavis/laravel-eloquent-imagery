<?php

namespace ZiffDavis\Laravel\EloquentImagery\Nova;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemManager;
use Laravel\Nova\Fields\Image as ImageField;
use Laravel\Nova\Http\Requests\Nova\Request;
use Laravel\Nova\Resource;
use ZiffDavis\Laravel\EloquentImagery\Eloquent\Image;

class EloquentImageryField extends ImageField
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    protected static $eloquentImageryFilesystem;

    protected $previewUrlModifiers = null;
    protected $thumbnailUrlModifiers = null;

    public function __construct($name, $attribute = null, $disk = 'public', $storageCallback = null)
    {
        if (!self::$eloquentImageryFilesystem) {
            self::$eloquentImageryFilesystem = app(FilesystemManager::class)
                ->disk(config('eloquent-imagery.filesystem', config('filesystems.default')));
        }

        parent::__construct($name, $attribute, $disk, $storageCallback);

        $this->store(function (NovaRequest $request, Model $model) {
            if ($request->hasFile('image')) {
                $model->{$this->attribute}->fromRequest($request);
            }
        });

        $this->preview(function (Image $eloquentImageryImage) {
            if (!$eloquentImageryImage->exists()) {
                return null;
            }

            return $eloquentImageryImage->url($this->previewUrlModifiers);
        });

        $this->thumbnail(function (Image $eloquentImageryImage) {
            if (!$eloquentImageryImage->exists()) {
                return null;
            }

            return $eloquentImageryImage->url($this->thumbnailUrlModifiers);
        });

        $this->delete(function (Request $request, Model $model) {
            /** @var Image $eloquentImageryImage */
            $eloquentImageryImage = $model->{$this->attribute};

            if (!$eloquentImageryImage->exists()) {
                return null;
            }

            $eloquentImageryImage->remove();
        });

        $this->download(function (NovaRequest $request, Model $model) {
            /** @var Image $eloquentImageryImage */
            $eloquentImageryImage = $model->{$this->attribute};

            if (!$eloquentImageryImage->exists()) {
                return null;
            }

            $thing = self::$eloquentImageryFilesystem->download($eloquentImageryImage->getStateProperties()['path']);
            return $thing;
        });
    }

    /**
     * @param $previewUrlModifiers
     * @return $this
     */
    public function previewUrlModifiers($previewUrlModifiers)
    {
        $this->previewUrlModifiers = $previewUrlModifiers;

        return $this;
    }

    /**
     * @param $thumbnailUrlModifiers
     * @return $this
     */
    public function thumbnailUrlModifiers($thumbnailUrlModifiers)
    {
        $this->thumbnailUrlModifiers = $thumbnailUrlModifiers;

        return $this;
    }
}

