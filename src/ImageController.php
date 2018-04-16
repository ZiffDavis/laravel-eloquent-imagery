<?php

namespace ZiffDavis\Laravel\EloquentImagery;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ImageController extends Controller
{
    public function handle(Request $request, $path)
    {
        $disk = config('eloquent_imagery.filesystem', config('filesystems.default'));
        $filesystem = app('filesystem')->disk($disk);

        // @todo image conversions, proper caching headers
        return response()
            ->make($filesystem->get($path))
            ->header('Content-type', $filesystem->getMimeType($path));
    }
}
