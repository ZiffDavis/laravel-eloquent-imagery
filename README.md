# Laravel Eloquent Imagery

## Description

`ziffdavis/laravel-eloquent-imagery` takes a unique approach to handling
images. Instead of treating images as model relations, they are treated
as attributes of a model. To this end, image information is stored
in a tables column (a model's attribute) at a given name. Additionally,
this library handles image modifications (resizing, triming,
backgrounds) when serving the image instead of at upload time. Images
are stored in any of the configured kinds of file storage that Laravel
supports.

Features:
- images are tracked in same table's column
- setup is handled by adding a trait and a method per image type
- modifications can be made by manipuliating the image's url
- placeholder generation is supported
- fallback to a placeholdler (mainily for dev purposes) is supported
  when the image is not on the disk 

### Other Good Libraries

If this solution to the image problem does not appeal to you, 
[Spatie's MediaLibrary](https://github.com/spatie/laravel-medialibrary)
is an excellent library that both treats images as
Models as a Relation, and also has a concept of "conversions" that can
be applied at upload time.

## Installation

First, install the package:

    $ composer require ziffdavis/laravel-eloquent-imagery
    
In Laravel 5.5+. this library will self-register. Next, you should
publish the vendor (config) files:

    $ artisan vendor:publish --provider=ZiffDavis\Laravel\EloquentImagery\EloquentImageryProvider

It is now ready to use.

## Usage

### Attaching An Image To A Model

In the simplest use case for a single image attached to a model, first
create a `json` column in a migration to handle this image:

```php
// in a table migration
$table->json('image')->nullable();
```

Next, in the model, add in the HasEloquentImagery trait and configure a class
property called `$eloquentImagery` like so:

```php
use ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery;

class Post extends Model
{
    use HasEloquentImagery;

    protected $eloquentImagery = [
        'image' => 'posts/{id}.{extension}',
    ];
}
```

### Attaching an Image Collection To A Model

An Image Collection is a ordered list of Image objects.  The collection
itself when hydrated has images that are indexable starting at 0. Each
collection has a concept of an *auto increment* number which is stored
in the collection (and the collection's serialization) so that Image
objects can take advantage of this in the path template.

In the simplest use case, using a `json` column like in the direct image
scenario above, add in the `HasEloquentImagery` trait as before, and 
use the `eloquentImageryCollection` method at construction to setup
the collection:

```php
use ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery;

class Post extends Model
{
    use HasEloquentImagery;

    protected $eloquentImagery = [
        'images' => [
            'path' => 'post/{id}/image-{index}.{extension}',
            'collection' => true
        ],
    ];
}
```

### Displaying An Image In A View

The following blade syntax assumes $post is a Model of type Post with
an `image` attribute.  This will generate a url
similar to `/imagery/post/11/image.png`:

```php
@if($bam->image->exists)
    <img src="{{ $bam->image->url() }}" width="20" />
@endif
```

Using modifiers when generating the url, a url generated such as
`/imagery/post/11/image.@size200x200@trim.png`

```php
@if($bam->image->exists)
    <img src="{{ $bam->image->url('size_200x200|trim) }}" width="20" />
@endif
```

## Image Modifiers

Eloquent Imagery supports a variety of url-based image modifiers. In code, they are specified in a pipe separated string
similar to the syntax for applying standard Laravel fitlers and validators (see above example). In a raw URL they 
are specified in a period (.) separated string. If modifiers takes an argument, the modifiers use an underscore (_) to 
separate the modifier from the argument. 

* **size** - set the desired width and height of the image. Takes width and height as an argument in `[width]x[height]` 
             format. Size has actual affect without specifying a **fit** option.
* **fit** - Options are `resize`, `lim`, `scale`, and `lpad`. 
    * **lim** - Limit. This resizes and image to be at most no bigger in either dimension than a bounding box specified 
                by the dimensions in the **size** parameter, and maintains the existing aspect ratio. Will not grow
                the image beyond its original size.
    * **lpad** - The same operation as **lim** except the image will be padded with extra pixels after the resizing
                 operation, yielding an image that is *exactly* the size specified in **size**.
    * **resize** - Resize the image to the dimensions in size. No aspect ratio preservation is done.
    * **scale** - Scale the image up beyond its original size, to a bounding box specified in **size**, allowing the
                  image to grow beyond its original size.
* **bgcolor** - Used in conjunction with **lpad**. If the image is a JPEG or non-transparent PNG, **bgcolor** is used 
                to determine the color of pixels to fill in the padding with. Specified as a hex value.
* **quality** - Set the compression quality for JPEG images to control filesize. Defaults to 75.
* **trim** - Automatically trim extra whitespace off the edges of an image. Specified as an integer tolerance value from 
             1-99.
* **crop** - Crop an image. Takes either a single integer value to crop off equally on all sides of the image, or a 
             4-place comma-separated list of x,y,width,height, describing coordinates from the top left of the image.
* **v** - Version of the image. This modifier is effectively a no-op but is expected to take a unix timestamp that is the
          modification date of the iamge, for cache busting.  

## Detailed Configuration

The following table describes the available configuration options:


##### `eloquent-imagery.filesystem`

Default: `env('IMAGERY_FILESYSTEM', 'public')`

This is the filesystem images will be stored on at the image's path.

##### `eloquent-imagery.render.enable`

Default: `true`

Whether or not to enable the render *route* and modification functionality.

##### `eloquent-imagery.render.path`

Default: `/imagery`

The path prefix the route will live at and serve images from.

##### `eloquent-imagery.render.placeholder.enable` 

Default: `env('IMAGERY_RENDER_PLACEHOLDER_ENABLE', false)`

Highly useful for dev purposes, consider enabling in local.

##### `eloquent-imagery.render.placeholder.filename`

default value `_placeholder_`

This identifies when a placeholder image is being requested.

##### `eloquent-imagery.render.placeholder.use_for_missing_files`

default value `env('IMAGERY_RENDER_PLACEHOLDER_USE_FOR_MISSING_FILES', false)`

If an image is requested that is not on the filesystem, enabling this
will serve a placeholder instead (useful for dev).

##### `eloquent-imagery.render.caching.enable`

default value `env('IMAGERY_RENDER_CACHING_ENABLE', true)`

Whether or not the controller should use full request caching

##### `eloquent-imagery.render.caching.driver`

default value `env('IMAGERY_RENDER_CACHING_DRIVER', 'disk')`

Cache to the disk.

##### `eloquent-imagery.render.caching.ttl`

default value `60`

How long the ttl for the cache is.

##### `eloquent-imagery.render.browser_cache_max_age`

default value `31536000` 

How long the browser should cache the image generated by this route for.

##### `eloquent-imagery.force_unmodified_image_rendering`

default value `env('IMAGERY_FORCE_UNMODIFIED_IMAGE_RENDERING', false)`

This will allow for the dynamic (controller) route or static route (link to storage, for example) to be seletively used based on if modifiers are present in the image request

## TODO

- support moving images as a result of updated path parts (attribute update, etc)
- support cloning of images (copying for cloned models)
- refactor the image "modifiers" to be extensible and more cohesive

## Updating

### Updating to 0.5.0

- make sure to rename the `config/eloquent_imagery.php` to config/eloquent-imagery.php`, probably a good idea to re-copy the original (or publish again).
- see the new method of configuring a model to use an image: use a property called `$eloqentImagery`
