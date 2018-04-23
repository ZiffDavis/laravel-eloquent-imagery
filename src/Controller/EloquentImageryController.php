<?php

namespace ZiffDavis\Laravel\EloquentImagery\Controller;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ZiffDavis\Laravel\EloquentImagery\Image\ImageModifier;
use ZiffDavis\Laravel\EloquentImagery\Image\PlaceholderImageFactory;

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
        $filesystem = app(FilesystemManager::class)->disk($disk);

        $pathinfo = pathinfo($path);
        $storagePath = $pathinfo['dirname'] . '/';

        $modifierOperators = [];

        $filenameWithoutExtension = $pathinfo['filename'];

        if (strpos($filenameWithoutExtension, '.') !== false) {
            $filenameWithoutExtension = pathinfo($filenameWithoutExtension, PATHINFO_FILENAME);
            $storagePath .= "{$filenameWithoutExtension}.{$pathinfo['extension']}";

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
            $storagePath .= $pathinfo['basename'];
        }

        // assume the mime type is PNG unless otherwise specified
        $mimeType = 'image/png';

        if ($filenameWithoutExtension === config('eloquent_imagery.render.placeholder.filename') && config('eloquent_imagery.render.placeholder.enable')) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $bytes = (new PlaceholderImageFactory())->create($placeholderWidth, $placeholderHeight, $modifierOperators['bgcolor'] ?? null);
        } else {
            try {
                $bytes = $filesystem->get($storagePath);
                $mimeType = $filesystem->getMimeType($storagePath);
            } catch (FileNotFoundException $e) {
                $bytes = null;
            }
        }

        if (!$bytes && config('eloquent_imagery.render.placeholder.use_for_missing_files') === true) {
            list ($placeholderWidth, $placeholderHeight) = isset($modifierOperators['size']) ? explode('x', $modifierOperators['size']) : [400, 400];
            $bytes = (new PlaceholderImageFactory())->create($placeholderWidth, $placeholderHeight, $modifierOperators['bgcolor'] ?? null);
        }

        // if there are no files that match, and placeholders are disabled give a 404
        if (!$bytes) {
            return abort(404);
        }

        $imageModifier = new ImageModifier();
        foreach ($modifierOperators as $operator => $arg) {
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
}
