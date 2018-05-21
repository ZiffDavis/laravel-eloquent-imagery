<?php

namespace ZiffDavis\Laravel\EloquentImagery\Test\Unit\Eloquent\TestAssets;

use Illuminate\Database\Eloquent\Model;
use ZiffDavis\Laravel\EloquentImagery\Eloquent\HasEloquentImagery;

class FooModel extends Model
{
    use HasEloquentImagery;

    public function __construct(array $attributes = [])
    {
        $this->eloquentImagery('image');
        parent::__construct($attributes);
    }
}
