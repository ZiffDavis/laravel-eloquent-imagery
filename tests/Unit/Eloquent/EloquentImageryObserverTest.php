<?php

namespace ZiffDavis\Laravel\EloquentImagery\Test\Unit\Eloquent;

use App\Console\Kernel;
use PHPUnit\Framework\TestCase;
use ZiffDavis\Laravel\EloquentImagery\Eloquent\EloquentImageryObserver;
use ZiffDavis\Laravel\EloquentImagery\Eloquent\Image;

class EloquentImageryObserverTest extends TestCase
{
    public function setup()
    {
        $app = require __DIR__ . '/../../../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
    }

    public function testRetrievedSetsStateOnImage()
    {
        $foo = new TestAssets\FooModel();
        $foo->setRawAttributes([
            'id' => 1,
            'image' => '{"path": "foo/bar.jpg", "extension": "jpg", "width": 1, "height": 1, "hash": "1234", "timestamp": 12345, "metadata": []}'
        ], true);

        $observer = new EloquentImageryObserver();
        $observer->retrieved($foo);

        $this->assertInstanceOf(Image::class, $foo->image);
        $this->assertEquals('foo/bar.jpg', $foo->image->toArray()['path']);
    }

    public function testSavingRestoresModelAttributes()
    {
        $foo = new TestAssets\FooModel();
        $foo->image->setStateProperties([
            'path' => 'foo/bar.jpg',
            'extension' => 'jpg',
            'width' => 1,
            'height' => 1,
            'hash' => '1234',
            'timestamp' => 12345,
            'metadata' => []
        ]);

        $observer = new EloquentImageryObserver();
        $observer->saving($foo);

        $this->assertEquals('{"path":"foo\/bar.jpg","extension":"jpg","width":1,"height":1,"hash":"1234","timestamp":12345,"metadata":[]}', $foo->image);
    }

    public function testSavedRestoresImage()
    {
        $foo = new TestAssets\FooModel();
        $foo->setRawAttributes([
            'id' => 1,
            'image' => '{"path": "foo/bar.jpg", "extension": "jpg", "width": 1, "height": 1, "hash": "1234", "timestamp": 12345, "metadata": []}'
        ], true);

        $observer = new EloquentImageryObserver();
        $observer->saved($foo);

        $this->assertInstanceOf(Image::class, $foo->image);
    }
}

