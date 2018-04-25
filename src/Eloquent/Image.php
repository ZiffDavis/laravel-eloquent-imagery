<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Image implements \JsonSerializable
{
    protected $attribute = null;
    protected $path = '';
    protected $filesystem;

    protected $model = null;

    protected $extension = '';
    protected $width = null;
    protected $height = null;
    protected $hash = '';
    protected $timestamp = 0;
    protected $metadata = [];

    protected $exists = false;
    protected $flush = false;
    protected $data = null;
    protected $remove = null;

    public function __construct($attribute, $path, $filesystem)
    {
        $this->attribute = $attribute;
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function exists()
    {
        return $this->exists;
    }

    public function url($modifiers = null)
    {
        $renderRouteEnabled = config('eloquent_imagery.render.enable');

        if ($renderRouteEnabled === false && $modifiers) {
            throw new \RuntimeException('Cannot process render options unless the rendering route is enabled');
        }

        // $forceUnmodifiedImageRendering = config('eloquent_imagery.render.force_unmodified_image_rendering');

        // || (!$modifiers && $forceUnmodifiedImageRendering === false)
        if ($renderRouteEnabled === false) {
            return Storage::disk($this->filesystem)->url($this->path);
        }

        if ($modifiers) {
            $modifierParts = explode('|', $modifiers);
            sort($modifierParts);
            $modifiers = implode('_', $modifierParts);
        }

        // keyed with [dirname, filename, basename, extension]
        $pathinfo = pathinfo($this->path);

        $pathWithModifiers =
            (($pathinfo['dirname'] !== '.') ? "{$pathinfo['dirname']}/" : '')
            . $pathinfo['filename']
            . ($modifiers ? ".{$modifiers}" : '')
            . ".{$pathinfo['extension']}";

        return url()->route('eloquent_imagery.render', $pathWithModifiers);
    }

    public function unserializeFromModel()
    {
        $properties = $this->model->getAttributeValue($this->attribute);

        if ($properties == '') {
            return;
        }

        if (is_string($properties)) {
            $properties = json_decode($properties, true);
        }

        foreach ($properties as $n => $v) {
            if (property_exists($this, $n)) {
                $this->{$n} = $v;
            }
        }

        $this->exists = true;
    }
    //
    // public function serializeToModel()
    // {
    //     $attributes = $this->model->getAttributes();
    //     $attributes[$this->attribute] = $this->getSerializedAttributeValue();
    // }

    public function getSerializedAttributeValue()
    {
        return json_encode([
            'path' => $this->path,
            'extension' => $this->extension,
            'width' => $this->width,
            'height' => $this->height,
            'hash' => $this->hash,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata
        ]);
    }

    public function fromRequest($request = null)
    {
        if (!$request) {
            $file = request()->file($this->attribute);
        } elseif (is_string($request)) {
            $file = request()->file($request);
        } elseif ($request instanceof Request) {
            $file = $request->file($this->attribute);
        } else {
            throw new \InvalidArgumentException('Unable to get image from request');
        }

        $this->setData($file);
        $this->updatePath();
    }

    public function setData($data)
    {
        if ($this->path && app('filesystem')->disk($this->filesystem)->exists($this->path)) {
            $this->remove = $this->path;
        }

        static $fInfo = null;
        if (!$fInfo) {
            $fInfo = new \Finfo;
        }

        if ($data instanceof UploadedFile) {
            $data = file_get_contents($data->getRealPath());
        }

        if (strpos($data, 'data:') === 0) {
            $data = file_get_contents($data);
        }

        list ($width, $height) = getimagesizefromstring($data);

        $mimeType = $fInfo->buffer($data, FILEINFO_MIME_TYPE);
        if (!$mimeType) {
            throw new \InvalidArgumentException('Mime type could not be discovered');
        }

        $this->flush = true;
        $this->data = $data;
        $this->width = $width;
        $this->height = $height;
        $this->timestamp = time();
        $this->hash = md5($data);

        switch ($mimeType) {
            case 'image/jpeg':
                $this->extension = 'jpg';
                break;
            case 'image/png':
                $this->extension = 'png';
                break;
            case 'image/gif':
                $this->extension = 'gif';
                break;
            default:
                throw new \RuntimeException('Unsupported mime-type for expected image: ' . $mimeType);
        }
    }

    public function updatePath()
    {
        $pathReplacements = [];
        $path = $this->path;
        preg_match_all('#{(\w+)}#', $path, $pathReplacements);

        foreach ($pathReplacements[1] as $pathReplacement) {
            if (in_array($pathReplacement, ['attribute', 'extension', 'width', 'height', 'hash', 'timestamp'])) {
                $path = str_replace("{{$pathReplacement}}", $this->{$pathReplacement}, $path);
                continue;
            }
            if (isset($this->metadata[$pathReplacement]) && $this->metadata[$pathReplacement] != '') {
                $path = str_replace("{{$pathReplacement}}", $this->metadata[$pathReplacement], $path);
                continue;
            }
            if ($this->model->offsetExists($pathReplacement) && $this->model->offsetGet($pathReplacement) != '') {
                $path = str_replace("{{$pathReplacement}}", $this->model->offsetGet($pathReplacement), $path);
                continue;
            }
        }

        $this->path = $path;
    }

    public function pathHasReplacements()
    {
        return (bool) preg_match('#{(\w+)}#', $this->path);
    }

    public function removeOnFlush()
    {
        $this->remove = $this->path;
    }

    public function flush()
    {
        /** @var Filesystem $filesystem */
        $filesystem = app('filesystem')->disk($this->filesystem);

        if ($this->remove) {
            $filesystem->delete($this->remove);
            $this->remove = null;
        }
        if (!$this->flush) {
            return;
        }
        if ($this->data) {
            if (strpos($this->path, '{') !== false) {
                throw new \RuntimeException('The image path still has an unresolved replacement in it ("{") and cannot be saved: ' . $this->path);
            }
            $filesystem->put($this->path, $this->data);
        }
        $this->flush = false;
    }

    public function __get($name)
    {
        if (!in_array($name, ['exists', 'metadata'])) {
            throw new \OutOfBoundsException("Property $name is not accessible");
        }
        return $this->{$name};
    }

    public function jsonSerialize()
    {
        return [
            'url' => $this->getUrl(),
            'meta' => $this->metadata
        ];
    }
}