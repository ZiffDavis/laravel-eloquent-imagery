<?php

namespace ZiffDavis\Laravel\EloquentImagery;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class EloquentImageryProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        $source = realpath(__DIR__ . '/../config/eloquent_imagery.php');
        $this->mergeConfigFrom($source, 'eloquent_imagery');

        // publish the configuration in cli local environment
        if ($this->app->runningInConsole() && $this->app->environment('local')) {
            $this->publishes([$source => config_path('eloquent_imagery.php')]);
        }


        if (config('eloquent_imagery.enable_placeholder_route')) {
            $this->checkPrequisites();
            $placeholderRoute = rtrim(config('eloquent_imagery.placeholder_route', '/_p'), '/');
            $router->get("{$placeholderRoute}/{path}", ImageController::class . '@placeholder')
                ->where('path', '(.*)')
                ->name('eloquent_imagery.placeholder');
            Blade::directive('placeholderImage', [BladeDirectives::class, 'placeholderUrl']);
        }

        if (config('eloquent_imagery.enable_render_route')) {
            $this->checkPrequisites();
            $imageRoute = rtrim(config('eloquent_imagery.render_route', '/_i'), '/');
            $router->get("{$imageRoute}/{path}", ImageController::class . '@render')
                ->where('path', '(.*)')
                ->name('eloquent_imagery.render');
        }
    }

    private function checkPrequisites()
    {
        if (!$this->app->runningInConsole()) {
            $ok = (extension_loaded('imagick') && class_exists('\Intervention\Image\Image'));
            if (!$ok) {
                throw new \RuntimeException("Eloquent Imagery requires ext/ImageMagick and Intervention/Image in order to render images");
            }
        }
    }
}
