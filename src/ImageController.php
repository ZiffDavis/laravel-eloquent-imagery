<?php

namespace ZiffDavis\Laravel\EloquentImagery;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class ImageController extends Controller
{
    protected $urlOperators = [
        'size'      => '/^size:([0-9]+x[0-9]+)$/', // set width
        'fit'       => '/^fit:([a-z]+)$/', // set height
        'grayscale' => '/^grayscale$/', // grayscale
        'quality'   => '/^quality:([0-9])+/', //quality, if applicable
        'bgcolor'   => '/^bg:([\da-f]{6})$/', // background hex
        'trim'      => '/^trim:(\d+)$/', // trim, tolerance
        'crop'      => '/^crop:([\d,?]+)$/' // crop operations
    ];

    public function render(Request $request, $path)
    {
        //@todo we need to make sure we lock down this endpoint against path traversal, otherwise it could leak anything within the app filesystem

        $cacheEnabled = (config('eloquent_imagery.enable_cache', false));
        if ($cacheEnabled && Cache::has($path)) {
            return Cache::get($path);
        }

        $disk = config('eloquent_imagery.filesystem', config('filesystems.default'));
        /** @var Filesystem $filesystem */
        $filesystem = app('filesystem')->disk($disk);

        $pathParts = explode("/", trim($path, "/"));
        $renderFilename = end($pathParts);
        $renderFilenameParts = explode(".", $renderFilename);
        $sourceImageFilename = $renderFilenameParts[0] . "." . end($renderFilenameParts);
        $storageFilePath = "/" . implode("/", array_slice($pathParts, 0, count($pathParts) - 1)) . "/" . $sourceImageFilename;

        $imageParams = [];
        $renderOperators = explode("_", $renderFilenameParts[1]);
        foreach ($renderOperators as $renderOperator) {
            $matches = [];
            foreach ($this->urlOperators as $operator => $regex) {
                if (preg_match($regex, $renderOperator, $matches)) {
                    $arg = null;
                    if (isset($matches[1])) {
                        $arg = $matches[1];
                    } else {
                        $arg = true;
                    }
                    $imageParams[$operator] = $arg;
                }
            }
        }

        try {
            $bytes = $filesystem->read($storageFilePath);
            $mimeType = $filesystem->getMimeType($storageFilePath);
        } catch (FileNotFoundException $e) {
            if (config('eloquent_imagery.enable_fallback_placeholders', false)) {
                $placeholder = new Placeholder($imageParams["width"] ?? 400, $imageParams["height"] ?? 400, $imageParams["bgcolor"] ?? null);
                $bytes = $placeholder->getImage();
                $mimeType = "image/png";
            } else {
                abort(404);
            }
        }

        $renderer = new Renderer();
        foreach ($imageParams as $operator => $arg) {
            call_user_func_array([$renderer, "set" . ucfirst($operator)], [$arg]);
        }
        $renderedBytes = $renderer->render($bytes);

        $browserCacheMaxAge = config('eloquent_imagery.browser_cache_max_age');
        $response = response()
            ->make($renderedBytes)
            ->header('Content-type', $mimeType)
            ->header('Cache-control', "max-age=$browserCacheMaxAge");

        if ($cacheEnabled) {
            Cache::put($path, $response, config('eloquent_imagery.cache_ttl', 60));
        }
        return $response;
    }

    public function placeholder(Request $request, $path)
    {
        $pathParts = explode(".", $path);
        if (count($pathParts) == 4) {
            $backgroundColor = $pathParts[0];
            if (ctype_xdigit($backgroundColor)) {
                foreach ($pathParts as $part) {
                    if ($part{0} == 'h') {
                        $height = str_replace('h', '', $part);
                    }
                    if ($part{0} == 'w') {
                        $width = str_replace('w', '', $part);
                    }
                }
                if ($backgroundColor && ($height ?? false) && ($width ?? false)) {
                    $placeHolder = new Placeholder($width, $height, $backgroundColor);
                    return response()->make($placeHolder->getImage())->header('Content-type', 'image/png');
                }
            }
        }
        abort(404);
    }

}
