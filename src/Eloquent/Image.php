<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read \ArrayObject $metadata
 */
class Image implements \JsonSerializable
{
    /** @var Filesystem */
    protected static $filesystem = null;

    protected $attribute = null;
    protected $pathTemplate = null;


    protected $path = '';
    protected $extension = '';
    protected $width = null;
    protected $height = null;
    protected $hash = '';
    protected $timestamp = 0;
    /** @var \ArrayObject */
    protected $metadata = null;

    protected $exists = false;
    protected $flush = false;
    protected $data = null;
    protected $removeAtPathOnFlush = null;

    public function __construct($attribute, $pathTemplate)
    {
        if (!self::$filesystem) {
            self::$filesystem = app(FilesystemManager::class)->disk(config('eloquent_imagery.filesystem', config('filesystems.default')));
        }

        $this->attribute = $attribute;
        $this->pathTemplate = $pathTemplate;
        $this->metadata = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
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

        if ($renderRouteEnabled === false) {
            return self::$filesystem->url($this->path);
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

    public function setStateProperties($properties)
    {
        $this->path = $properties['path'];
        $this->extension = $properties['extension'];
        $this->width = $properties['width'];
        $this->height = $properties['height'];
        $this->hash = $properties['hash'];
        $this->timestamp = $properties['timestamp'];
        $this->metadata->exchangeArray($properties['metadata']);
        $this->exists = true;
    }

    public function getStateProperties()
    {
        return [
            'path' => $this->path,
            'extension' => $this->extension,
            'width' => $this->width,
            'height' => $this->height,
            'hash' => $this->hash,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata->getArrayCopy()
        ];
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
        if ($this->path && self::$filesystem->exists($this->path)) {
            $this->removeAtPathOnFlush = $this->path;
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

        $this->path = $this->pathTemplate;
        $this->exists = true;
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

    public function updatePath(Model $model = null, $fromTemplate = false)
    {
        $pathReplacements = [];
        $path = ($fromTemplate) ? $this->pathTemplate : $this->path;
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
            if ($model && $model->offsetExists($pathReplacement) && $model->offsetGet($pathReplacement) != '') {
                $path = str_replace("{{$pathReplacement}}", $model->offsetGet($pathReplacement), $path);
                continue;
            }
        }

        $this->path = $path;
    }

    public function pathHasReplacements()
    {
        return (bool) preg_match('#{(\w+)}#', $this->path);
    }

    public function isFullyRemoved()
    {
        return ($this->flush === true && $this->removeAtPathOnFlush !== '' && $this->path === '');
    }

    public function remove()
    {
        if ($this->path == '') {
            throw new \RuntimeException('Called remove on an image that has no path');
        }
        $this->exists = false;
        $this->flush = true;
        $this->removeAtPathOnFlush = $this->path;

        $this->path = '';
        $this->extension = '';
        $this->width = null;
        $this->height = null;
        $this->hash = '';
        $this->timestamp = 0;
        $this->metadata->exchangeArray([]);
    }

    public function flush()
    {
        if (!$this->flush) {
            return;
        }

        if ($this->removeAtPathOnFlush) {
            self::$filesystem->delete($this->removeAtPathOnFlush);
            $this->remove = null;
        }

        if ($this->data) {
            if ($this->pathHasReplacements()) {
                throw new \RuntimeException('The image path still has an unresolved replacement in it ("{...}") and cannot be saved: ' . $this->path);
            }
            self::$filesystem->put($this->path, $this->data);
        }

        $this->flush = false;
    }

    public function __get($name)
    {
        if ($name === 'metadata') {
            return $this->metadata;
        }

        if (!in_array($name, ['exists', 'metadata'])) {
            throw new \OutOfBoundsException("Property $name is not accessible");
        }
        return $this->{$name};
    }

    public function toArray()
    {
        return [
            'path' => $this->path,
            'extension' => $this->extension,
            'width' => $this->width,
            'height' => $this->height,
            'hash' => $this->hash,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata->getArrayCopy()
        ];
    }

    public function jsonSerialize()
    {
        if ($this->exists) {
            return [
                'url' => $this->url(),
                'meta' => $this->metadata
            ];
        }

        return null;
    }

    public function __clone()
    {
        $this->metadata = clone $this->metadata;
    }
}