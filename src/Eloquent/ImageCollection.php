<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ImageCollection implements \ArrayAccess, Arrayable, \Countable, \IteratorAggregate, Jsonable, \JsonSerializable
{
    protected $attribute = null;
    protected $pathTemplate = null;
    protected $filesystem;

    /** @var Image[] */
    protected $images = [];
    protected $autoinc = 1;

    protected $deletedImages = [];

    public function __construct($attribute, $pathTemplate, $filesystem)
    {
        $this->attribute = $attribute;
        $this->pathTemplate = $pathTemplate;
        $this->filesystem = $filesystem;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function offsetExists($offset)
    {
        return isset($this->images[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->images[$offset])) {
            throw new \InvalidArgumentException("There is no image at offset $offset in this collection");
        }

        return $this->images[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $offset = count($this->images);
            $this->images[$offset] = $image = new Image($this->attribute, $this->pathTemplate, $this->filesystem);
            $image->metadata->index = $this->autoinc++;
        } else {
            $image = $this->images[$offset];
        }

        ($value instanceof Request) ? $image->fromRequest($value) : $image->setData($value);
    }

    public function offsetUnset($offset)
    {
        if (!isset($this->images[$offset])) {
            throw new \RuntimeException("Image does not exist at offset $offset");
        }

        // find image at offset, set to remove on flush
        $image = $this->images[$offset];
        $image->remove();

        $this->deletedImages[] = $image;

        unset($this->images[$offset]);
    }

    public function toArray()
    {
        return $this->images;
    }

    public function count()
    {
        return count($this->images);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->images);
    }

    public function toJson($options = 0)
    {
        return json_encode($this->getStateProperties(), $options);
    }

    public function jsonSerialize()
    {
        return $this->getStateProperties();
    }

    public function pathHasReplacements()
    {
        foreach ($this->images as $image) {
            if ($image->pathHasReplacements()) {
                return true;
            }
        }
        return false;
    }

    public function purgeRemovedImages()
    {
        foreach ($this->images as $i => $image) {
            if ($image->isFullyRemoved()) {
                $this->deletedImages[] = $image;
                unset($this->images[$i]);
            }
        }
    }

    public function flush()
    {
        foreach ($this->deletedImages as $image) {
            $image->flush();
        }
        foreach ($this->images as $image) {
            $image->flush();
        }
    }

    public function updatePath(Model $model = null, $fromTemplate = false)
    {
        foreach ($this->images as $image) {
            $image->updatePath($model, $fromTemplate);
        }
    }

    public function setStateProperties(array $properties)
    {
        foreach ($properties['images'] as $imageState) {
            $image = new Image($this->attribute, $this->pathTemplate, $this->filesystem);
            $image->setStateProperties($imageState);
            $this->images[] = $image;
        }

        $this->autoinc = $properties['autoinc'];
    }

    public function getStateProperties()
    {
        $imagesState = [];

        foreach ($this->images as $image) {
            $imagesState[] = $image->getStateProperties();
        }

        return [
            'autoinc' => $this->autoinc,
            'images' => $imagesState
        ];
    }
}
