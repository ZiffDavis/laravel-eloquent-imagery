<?php

namespace ZiffDavis\Laravel\EloquentImagery\Test\Unit\Eloquent\TestAssets;

use Illuminate\Database\Eloquent\Model;
use ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery;
use ZiffDavis\Laravel\EloquentImagery\Eloquent\Image;

/**
 * @property Image $image
 */
class FooModel extends Model
{
    use HasEloquentImagery;

    protected $eloquentImagery = [
        'image' => 'images/{id}.{extension}'
    ];
}
