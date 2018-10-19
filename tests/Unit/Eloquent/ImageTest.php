<?php

namespace ZiffDavis\Laravel\EloquentImagery\Test\Unit\Eloquent;

use App\Console\Kernel;
use PHPUnit\Framework\TestCase;

class EloquentImageryObserverTest extends TestCase
{
    public function setup()
    {
        $app = require __DIR__ . '/../../../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
    }

    public function testRemoveCanBeCalledMultipleTimes()
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
        $foo->image->remove();
        $foo->image->remove();

        $expected = [
            "path" => "",
            "extension" => "",
            "width" => null,
            "height" => null,
            "hash" => "",
            "timestamp" => 0,
            "metadata" => []
        ];

        $this->assertSame($expected, $foo->image->getStateProperties());
    }
}

