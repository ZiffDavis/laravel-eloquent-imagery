<?php
namespace ZiffDavis\Laravel\EloquentImagery;

class BladeDirectives
{
    public static function placeholderUrl($args)
    {
        $individualArgs = explode(",", $args);
        if (count($individualArgs) == 2) {
            list($width, $height) = $individualArgs;
            $backgroundColor = "cccccc";
        } else if (count($individualArgs) == 3) {
            list($width, $height, $backgroundColor) = $individualArgs;
        } else {
            throw new \InvalidArgumentException("@placeholderImage takes exactly 2 or 3 arguments");
        }
        $path = "$backgroundColor.w$width.h$height.png";
        $url = route('eloquent_imagery.placeholder', $path);
        return "$url";
    }
}