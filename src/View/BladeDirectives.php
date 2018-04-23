<?php
namespace ZiffDavis\Laravel\EloquentImagery\View;

class BladeDirectives
{
    public static function placeholderImageUrl($args)
    {
        // $individualArgs = explode(",", $args);
        //
        // if (count($individualArgs) == 2) {
        //     list($width, $height) = $individualArgs;
        //     $backgroundColor = 'cccccc';
        // } else if (count($individualArgs) == 3) {
        //     list($width, $height, $backgroundColor) = $individualArgs;
        // } else {
        //     throw new \InvalidArgumentException('@placeholderImage takes exactly 2 or 3 arguments');
        // }

        $placeholderFilename = config('eloquent_imagery.render.placeholder.filename');

        $path = "{$placeholderFilename}.{$args}.png";
        return route('eloquent_imagery.render', $path);
    }
}