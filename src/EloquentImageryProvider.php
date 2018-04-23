<?php

namespace ZiffDavis\Laravel\EloquentImagery;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Image as InterventionImage;

class EloquentImageryProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        $packageConfigPath = realpath(__DIR__ . '/../config/eloquent_imagery.php');
        $this->mergeConfigFrom($packageConfigPath, 'eloquent_imagery');

        // publish the configuration in cli local environment
        if ($this->app->runningInConsole() && $this->app->environment('local')) {
            $this->publishes([$packageConfigPath => config_path('eloquent_imagery.php')], 'config');
        }

        if (config('eloquent_imagery.render.enable')) {
            if (!$this->app->runningInConsole() && !extension_loaded('imagick') && !class_exists(InterventionImage::class)) {
                throw new \RuntimeException('Eloquent Imagery requires ext/ImageMagick and intervention/image package in order to render images');
            }

            $imageRoute = rtrim(config('eloquent_imagery.render.route', '/imagery'), '/');

            $router->get("{$imageRoute}/{path}", Controller\EloquentImageryController::class . '@render')
                ->where('path', '(.*)')
                ->name('eloquent_imagery.render');

            Blade::directive('placeholderImageUrl', [View\BladeDirectives::class, 'placeholderImageUrl']);
        }
    }

    /*
    private function checkPrequisites()
    {
        if (!$this->app->runningInConsole() && !extension_loaded('imagick') && !class_exists(InterventionImage::class)) {
            throw new \RuntimeException('Eloquent Imagery requires ext/ImageMagick and Intervention/Image in order to render images');
        }
    }
    */
}
