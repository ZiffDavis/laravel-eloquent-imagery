<?php
namespace ZiffDavis\Laravel\EloquentImagery\View;

class BladeDirectives
{
    public static function placeholderImageUrl($args)
    {
        $placeholderFilename = config('eloquent-imagery.render.placeholder.filename');
        $path = "{$placeholderFilename}.{$args}.png";
        return route('eloquent-imagery.render', $path);
    }
}
