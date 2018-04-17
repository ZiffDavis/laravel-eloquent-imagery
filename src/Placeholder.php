<?php
namespace ZiffDavis\Laravel\EloquentImagery;

use Intervention\Image\AbstractFont;
use Intervention\Image\ImageManager;

class Placeholder
{
    /** @var  int */
    protected $width;
    /** @var  int */
    protected $height;
    /** @var  string */
    protected $backgroundColor = 'aaaaaa';

    /**
     * Placeholder constructor.
     * @param int $width
     * @param int $height
     * @param string $backgroundColor
     */
    public function __construct($width, $height, $backgroundColor = null)
    {
        $this->width = $width;
        $this->height = $height;
        if ($backgroundColor) {
            $this->backgroundColor = $backgroundColor;
        }

    }

    public function getImage()
    {
        $imageManager = new ImageManager(['driver' => 'imagick']);
        $img = $imageManager->canvas($this->width, $this->height, "$this->backgroundColor");
        $img->text("{$this->width}x{$this->height}", $this->width / 2, $this->height / 2, function(AbstractFont $font) {
            $font->align("center");
            $font->valign("middle");
            $font->color("000000");
            $font->file(__DIR__ . '/../OverpassMono-Regular.ttf');
            $font->size(ceil((.9 * $this->width) / 7));
        });
        return $img->encode("png")->__toString();
    }
}