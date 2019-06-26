<?php

namespace ZiffDavis\Laravel\EloquentImagery\Eloquent;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use IteratorAggregate;
use JsonSerializable;

/**
 * @mixin Collection
 */
class ImageCollection implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable
{
    use ForwardsCalls;

    /** @var Image */
    protected $imagePrototype;

    /** @var Collection|Image[] */
    protected $images;

    /** @var int */
    protected $autoincrement = 1;

    /** @var Collection */
    protected $metadata;

    protected $deletedImages = [];

    public function __construct($imagePrototype)
    {
        $this->imagePrototype = $imagePrototype;
        $this->images = new Collection;
        $this->metadata = new Collection;
    }

    public function createImage($attributeData = [])
    {
        $image = clone $this->imagePrototype;

        if ($attributeData) {
            $image->setStateFromAttributeData($attributeData);
        }

        return $image;
    }

    public function getCollection()
    {
        return $this->images;
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->images->getIterator();
    }

    /**
     * Determine if the given item exists.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->images->has($key);
    }

    /**
     * Get the item at the given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->images->get($key);
    }

    /**
     * Set the item at the given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (!$value instanceof Image) {
            $value = $this->createImage($value);
        }

        $this->images->put($key, $value);
    }

    /**
     * Unset the item at the given key.
     *
     * @param  mixed  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->deletedImages[] = $this->images[$key];

        $this->images[$key]->remove();

        $this->images->forget($key);
    }

    /**
     * Get the number of items for the current page.
     *
     * @return int
     */
    public function count()
    {
        return $this->images->count();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'images' => $this->images->toArray(),
            'metadata' => $this->metadata->toArray(),
            'autoincrement' => 1,
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function setStateFromAttributeData($attributeData)
    {
        $this->autoincrement = $attributeData['autoinc'] ?? $attributeData['autoincrement'] ?? 1;

        foreach ($attributeData['images'] as $imageState) {
            $this->createImage($imageState);
            $this->images->push($image);
        }

        if ($attributeData['metadata']) {
            foreach ($attributeData['metadata'] as $key => $value) {
                $this->metadata[$key] = $value;
            }
        }
    }

    public function getStateAsAttributeData()
    {
        return [
            'autoincrement' => $this->autoincrement,
            'images'        => $this->images->map(function (Image $image) {
                return $image->getStateAsAttributeData();
            }),
            'metadata'      => $this->metadata->toArray()
        ];
    }

    public function pathHasReplacements()
    {
        return $this->images->every(function ($image) {
            return $this->pathHasReplacements();
        });
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

    public function updatePath(Model $model = null, $fromTemplate = false)
    {
        $this->images->each(function (Image $images) {
            $image->updatePath($model, $extra, $fromTemplate);
        });
    }

    /**
     * Called to remove all the images from this collection, generally in a workflow to remove an entire entity
     */
    public function remove()
    {
        $this->images->each(function (Image $image) {
            $image->remove();
        });
    }

    public function flush()
    {
        foreach ($this->deletedImages as $image) {
            $image->flush();
        }

        $this->images->each(function (Image $image) {
            $image->flush();
        });
    }

    /**
     * Make dynamic calls into the collection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->getCollection(), $method, $parameters);
    }
}

// class ImageCollection implements \ArrayAccess, Arrayable, \Countable, \IteratorAggregate, Jsonable, \JsonSerializable
// {
//     protected $attribute = null;
//     protected $pathTemplate = null;
//
//     /** @var Image[] */
//     protected $images = [];
//     protected $autoinc = 1;
//     protected $metadata = [];
//
//     protected $deletedImages = [];
//
//     public function __construct($attribute, $pathTemplate)
//     {
//         $this->attribute = $attribute;
//         $this->pathTemplate = $pathTemplate;
//     }
//
//     public function createImage($incrementAutoinc = true)
//     {
//         $image = new Image($this->pathTemplate);
//         $image->metadata->index = $this->autoinc++;
//         return $image;
//     }
//
//     public function exchangeArray($images)
//     {
//         $array = $this->images;
//         $this->images = [];
//         return $array;
//     }
//
//     public function exists()
//     {
//         return true;
//     }
//
//     public function offsetExists($offset)
//     {
//         return isset($this->images[$offset]);
//     }
//
//     public function offsetGet($offset)
//     {
//         if (!isset($this->images[$offset])) {
//             throw new \InvalidArgumentException("There is no image at offset $offset in this collection");
//         }
//
//         return $this->images[$offset];
//     }
//
//     public function offsetSet($offset, $value)
//     {
//         // get image object
//         if ($offset && isset($this->images[$offset])) {
//             $image = $this->images[$offset];
//         } elseif ($value instanceof Image) {
//             $image = $value;
//         } else {
//             $image = $this->createImage();
//         }
//
//         if ($offset === null) {
//             $offset = count($this->images);
//             $this->images[$offset] = $image = $this->createImage();
//         } else {
//             $this->images[$offset] = $image;
//         }
//
//         if (is_string($value)) {
//             $image->setData($value);
//         }
//     }
//
//     public function offsetUnset($offset)
//     {
//         if (!isset($this->images[$offset])) {
//             throw new \RuntimeException("Image does not exist at offset $offset");
//         }
//
//         // find image at offset, set to remove on flush
//         $image = $this->images[$offset];
//         $image->remove();
//
//         $this->deletedImages[] = $image;
//
//         unset($this->images[$offset]);
//     }
//
//     public function toArray()
//     {
//         return $this->images;
//     }
//
//     public function count()
//     {
//         return count($this->images);
//     }
//
//     public function getIterator()
//     {
//         return new \ArrayIterator($this->images);
//     }
//
//     public function toJson($options = 0)
//     {
//         return json_encode($this->getStateProperties(), $options);
//     }
//
//     public function jsonSerialize()
//     {
//         return $this->getStateProperties();
//     }
//
//     public function pathHasReplacements()
//     {
//         foreach ($this->images as $image) {
//             if ($image->pathHasReplacements()) {
//                 return true;
//             }
//         }
//         return false;
//     }
//
//     public function purgeRemovedImages()
//     {
//         foreach ($this->images as $i => $image) {
//             if ($image->isFullyRemoved()) {
//                 $this->deletedImages[] = $image;
//                 unset($this->images[$i]);
//             }
//         }
//     }
//
//     public function remove()
//     {
//         foreach ($this->images as $image) {
//             $image->remove();
//         }
//     }
//
//     public function flush()
//     {
//         foreach ($this->deletedImages as $image) {
//             $image->flush();
//         }
//         foreach ($this->images as $image) {
//             $image->flush();
//         }
//     }
//
//     public function updatePath(Model $model = null, $fromTemplate = false)
//     {
//         foreach ($this->images as $image) {
//             $image->updatePath($model, $fromTemplate);
//         }
//     }
//
//     public function setStateProperties(array $properties)
//     {
//         $this->autoinc = $properties['autoinc'] ?? 1;
//
//         foreach ($properties['images'] as $imageState) {
//             $image = new Image($this->attribute, $this->pathTemplate);
//             $image->setStateProperties($imageState);
//             $this->images[] = $image;
//         }
//
//         $this->metadata = $properties['metadata'] ?? [];
//     }
//
//     public function getStateProperties()
//     {
//         $imagesState = [];
//
//         foreach ($this->images as $image) {
//             $imagesState[] = $image->getStateProperties();
//         }
//
//         return [
//             'autoinc' => $this->autoinc,
//             'images' => $imagesState,
//             'metadata' => $this->metadata
//         ];
//     }
//
//     public function __get($name)
//     {
//         switch ($name) {
//             case 'autoinc':
//                 return $this->autoinc;
//             case 'images':
//                 return $this->images;
//             default:
//                 throw new \InvalidArgumentException($name . ' is not a valid property on ' . __CLASS__);
//         }
//     }
// }
