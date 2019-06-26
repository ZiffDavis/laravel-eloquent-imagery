<?php

namespace ZiffDavis\Laravel\EloquentImagery\Test\Unit\Eloquent;

use ZiffDavis\Laravel\EloquentImagery\Eloquent\Image;
use ZiffDavis\Laravel\EloquentImagery\Eloquent\ImageCollection;
use ZiffDavis\Laravel\EloquentImagery\EloquentImageryProvider;

class ImageCollectionTest extends AbstractTestCase
{
    protected function getPackageProviders($app)
    {
        return [EloquentImageryProvider::class];
    }

    public function testSetStateFromDataAttribute()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}'));

        $state = [
            'autoincrement' => 10,
            'images' => [
                [
                    'path'      => 'foo/bar.jpg',
                    'extension' => 'jpg',
                    'width'     => 1,
                    'height'    => 1,
                    'hash'      => '1234567890',
                    'timestamp' => 12345,
                    'metadata'  => []
                ]
            ],
            'metadata' => []
        ];

        $imageCollection->setStateFromAttributeData($state);

        $this->assertCount(1, $imageCollection);
        $image = $imageCollection[0];

        $this->assertEquals('foo/bar.jpg', $image->path);
        $this->assertTrue($image->exists());
        $this->assertEquals('http://localhost/imagery/foo/bar.jpg', $image->url());
    }

    public function testOffsetSet()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}'));

        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));

        $this->assertCount(1, $imageCollection);
    }

    // public function testUpdatePath()
    // {
    //     $foo = new TestAssets\FooModel();
    //     $foo->setRawAttributes(['id' => 20], true);
    //
    //     $pngImageData = file_get_contents(__DIR__ . '/TestAssets/30.png');
    //
    //     $image = new Image('foo/{id}.{extension}');
    //     $image->setData($pngImageData);
    //     $updatedParts = $image->updatePath([], $foo);
    //
    //     $this->assertEquals('foo/20.png', $image->path);
    //     $this->assertEquals(['id', 'extension'], $updatedParts);
    //
    //     $image = new Image('foo/{outside_var}.{extension}');
    //     $image->setData($pngImageData);
    //     $updatedParts = $image->updatePath(['outside_var' => 'foobar'], $foo);
    //
    //     $this->assertEquals('foo/foobar.png', $image->path);
    //     $this->assertEquals(['outside_var', 'extension'], $updatedParts);
    // }
    //
    // public function testPathHasReplacements()
    // {
    //     $image = new Image('foo/{id}.{extension}');
    //     $image->setData(file_get_contents(__DIR__ . '/TestAssets/30.png'));
    //
    //     $this->assertTrue($image->pathHasReplacements());
    //
    //     $image->updatePath(['id' => 5, 'extension' => 'jpg'], new TestAssets\FooModel);
    //
    //     $this->assertFalse($image->pathHasReplacements());
    // }
}

