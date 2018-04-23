<?php

namespace ZiffDavis\Laravel\EloquentImagery\Controller;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use ZiffDavis\Laravel\EloquentImagery\Image\ImageModifier;

class EloquentImageryController extends Controller
{
    /**
     * Parsers for url route modifiers
     *
     * @var array
     */
    protected $urlOperators = [
        'size'      => '/^size:([0-9]+x[0-9]+)$/', // set width
        'fit'       => '/^fit:([a-z]+)$/', // set height
        'grayscale' => '/^grayscale$/', // grayscale
        'quality'   => '/^quality:([0-9])+/', //quality, if applicable
        'bgcolor'   => '/^bg:([\da-f]{6})$/', // background hex
        'trim'      => '/^trim:(\d+)$/', // trim, tolerance
        'crop'      => '/^crop:([\d,?]+)$/' // crop operations
    ];

    public function render($path)
    {
        $cacheEnabled = config('eloquent_imagery.caching.enable', false);

        if ($cacheEnabled && Cache::has($path)) {
            return Cache::get($path);
        }

        // Path traversal detection: 404 the user, no need to give additional information
        abort_if((in_array($path[0], ['.', '/']) || strpos($path, '../') !== false), 404);

        $disk = config('eloquent_imagery.filesystem', config('filesystems.default'));

        /** @var Filesystem $filesystem */
        $filesystem = app(Filesystem::class)->disk($disk);

        $pathinfo = pathinfo($path);
        $storage = $pathinfo['dirname'] . '/';

        $modifierOperators = [];

        if (strpos($pathinfo['filename'], '.') !== false) {
            $storage .= pathinfo($pathinfo['filename'], PATHINFO_FILENAME) . ".{$pathinfo['extension']}";

            $modifierSpecs = explode('_', pathinfo($pathinfo['filename'], PATHINFO_EXTENSION));

            foreach ($modifierSpecs as $modifierSpec) {
                $matches = [];
                foreach ($this->urlOperators as $operator => $regex) {
                    if (preg_match($regex, $modifierSpec, $matches)) {
                        $arg = null;
                        if (isset($matches[1])) {
                            $arg = $matches[1];
                        } else {
                            $arg = true;
                        }
                        $modifierOperators[$operator] = $arg;
                    }
                }
            }
        } else {
            $storage .= $pathinfo['basename'];
        }



        // foreach (explode('_', $modifiersString) as $renderOperator) {

            // $pathParts = explode('/', trim($path, '/'));
            // $renderFilename = end($pathParts);
            // $renderFilenameParts = explode(".", $renderFilename);
            // $sourceImageFilename = $renderFilenameParts[0] . "." . end($renderFilenameParts);
            // $storageFilePath = "/" . implode("/", array_slice($pathParts, 0, count($pathParts) - 1)) . "/" . $sourceImageFilename;
            //
            // $imageParams = [];
            // $renderOperators = explode("_", $renderFilenameParts[1]);
            // foreach ($renderOperators as $renderOperator) {

        // }

        try {
            $bytes = $filesystem->read($storage);
            $mimeType = $filesystem->getMimeType($storage);
        } catch (FileNotFoundException $e) {
            if (config('eloquent_imagery.render.placeholder.use_for_missing_files', false)) {
                $bytes = (new PlaceholderImageFactory())->create(
                    $imageParams['width'] ?? 400,
                    $imageParams['height'] ?? 400,
                    $imageParams['bgcolor'] ?? null
                );
                $mimeType = 'image/png';
            } else {
                return abort(404);
            }
        }

        $imageModifier = new ImageModifier();
        foreach ($imageParams as $operator => $arg) {
            call_user_func_array([$imageModifier, 'set' . ucfirst($operator)], [$arg]);
        }
        $bytes = $imageModifier->modify($bytes);

        $browserCacheMaxAge = config('eloquent_imagery.render.browser_cache_max_age');

        $response = response()
            ->make($bytes)
            ->header('Content-type', $mimeType)
            ->header('Cache-control', "max-age=$browserCacheMaxAge");

        if ($cacheEnabled) {
            Cache::put($path, $response, config('eloquent_imagery.render.caching.ttl', 60));
        }

        return $response;
    }

    // public function placeholder(Request $request, $path)
    // {
    //     $pathParts = explode('.', $path);
    //     if (count($pathParts) == 4) {
    //         $backgroundColor = $pathParts[0];
    //         if (ctype_xdigit($backgroundColor)) {
    //             foreach ($pathParts as $part) {
    //                 if ($part{0} == 'h') {
    //                     $height = str_replace('h', '', $part);
    //                 }
    //                 if ($part{0} == 'w') {
    //                     $width = str_replace('w', '', $part);
    //                 }
    //             }
    //             if ($backgroundColor && ($height ?? false) && ($width ?? false)) {
    //                 $placeHolder = new Placeholder($width, $height, $backgroundColor);
    //                 return response()->make($placeHolder->getImage())->header('Content-type', 'image/png');
    //             }
    //         }
    //     }
    //     abort(404);
    // }

}
