<?php
namespace ZiffDavis\Laravel\EloquentImagery;

use Intervention\Image\Constraint;
use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class Renderer
{
    const FIT_PAD_LIMIT = 'lpad';
    const FIT_RESIZE = 'resize';
    const FIT_LIMIT = 'lim';
    const FIT_SCALE = 'scale';

    /** @var int */
    protected $originalWidth;
    /** @var int */
    protected $originalHeight;
    /** @var string */
    protected $fit;
    /** @var int */
    protected $width;
    /** @var int */
    protected $height;
    /** @var bool */
    protected $grayscale;
    /** @var int */
    protected $quality = 75;
    /** @var string */
    protected $sourceBytes;
    /** @var string */
    protected $renderedBytes;
    /** @var string */
    protected $bgcolor;
    /** @var int */
    protected $trimTolerance;
    /** @var  int|array */
    protected $crop;


    /**
     * @param int $width
     * @param int $height
     */
    public function setSize($dimensions)
    {
        list($this->width, $this->height) = explode("x", $dimensions);
    }

    /**
     * @param int $width
     * @param int $height
     */
    public function setFit($fit)
    {
        $this->fit = $fit;
    }

    /**
     * @param int $width
     */
    public function setWidth(int $width)
    {
        $this->width = $width;
    }

    /**
     * @param int $height
     */
    public function setHeight(int $height)
    {
        $this->height = $height;
    }

    /**
     * @param boolean $grayscale
     */
    public function setGrayscale(bool $grayscale = true)
    {
        $this->grayscale = $grayscale;
    }

    /**
     * @param int $quality
     */
    public function setQuality(int $quality)
    {
        $this->quality = $quality;
    }

    /**
     * @param string $bgcolor
     */
    public function setBgcolor(string $bgcolor)
    {
        $this->bgcolor = $bgcolor;
    }

    /**
     * @param int $trimTolerance
     */
    public function setTrimTolerance(int $trimTolerance)
    {
        if ($trimTolerance > 99) {
            $trimTolerance = 99;
        }
        if ($trimTolerance < 1) {
            $trimTolerance = 1;
        }
        $this->trimTolerance = $trimTolerance;
    }

    /**
     * @param array|int $crop
     */
    public function setCrop($crop)
    {
        if (strpos($crop, ",") !== false) {
            $this->crop = explode(",", $crop);
        } else {
            $this->crop = $crop;
        }
    }



    public function render($bytes)
    {
        $imageManager = new ImageManager(['driver' => 'imagick']);
        $img = $imageManager->make($bytes);
        $originaltype = $img->mime();
        $encodeType = str_replace(["image/", "jpeg"], ["", "jpg"], $originaltype);

        if ($originaltype == "image/jpeg") {
            $img->filter(new class implements FilterInterface {
                public function applyFilter(\Intervention\Image\Image $image)
                {
                    $core = $image->getCore();
                    if ($core instanceof \Imagick) {
                        $core->stripImage();
                        if ($core->getImageColorspace() == \Imagick::COLORSPACE_CMYK) {
                            $core->transformImageColorspace(\Imagick::COLORSPACE_RGB);
                        }
                    }
                    return $image;
                }
            });
            /** @var \Imagick $core */
            $core = $img->getCore();
            $core->setSamplingFactors(["2x2", "1x1", "1x1"]);
        }

        if ($this->trimTolerance) {
            $img->trim(null, null, $this->trimTolerance);
        }

        if ($this->crop) {
            if (is_array($this->crop)) {
                $x = $this->crop[3];
                $y = $this->crop[0];
                $width = $img->width() - $x - $this->crop[1];
                $height = $img->height() - $y - $this->crop[2];
            } else {
                $x = $this->crop;
                $y = $this->crop;
                $width = $img->width() - (2 * $this->crop);
                $height = $img->height() - (2 * $this->crop);
            }
            $img->crop($width, $height, $x, $y);
        }

        switch ($this->fit) {
            case self::FIT_PAD_LIMIT:
                $canvasHeight = $this->height ?? $img->height();
                $canvasWidth = $this->width ?? $img->width();
                $canvas = $imageManager->canvas($canvasWidth, $canvasHeight, $this->bgcolor);
                $img->heighten($canvasHeight, function(Constraint $c) {
                    $c->upsize();
                });
                $img->widen($canvasWidth, function(Constraint $c) {
                    $c->upsize();
                });
                $canvas->insert($img, 'center');
                $img = $canvas;
                break;
            case self::FIT_LIMIT:
                $height = $this->height ?? $img->height();
                $width = $this->width ?? $img->width();
                $img->heighten($height, function(Constraint $c) {
                    $c->upsize();
                });
                $img->widen($width, function(Constraint $c) {
                    $c->upsize();
                });
                break;
            case self::FIT_SCALE:
                $height = $this->height ?? $img->height();
                $width = $this->width ?? $img->width();
                $img->resize($this->width, $this->height, function(Constraint $c) {
                    $c->aspectRatio();
                });
                break;
            case self::FIT_RESIZE:
                $img->resize($this->width, $this->height);
                break;
        }

        if ($this->grayscale) {
            $img->greyscale();
        }
        return $img->encode($encodeType, $this->quality)->__toString();
    }
}