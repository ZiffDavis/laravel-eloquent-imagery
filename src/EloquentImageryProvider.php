<?php

namespace ZiffDavis\Laravel\EloquentImagery;

use Illuminate\Routing\Router;
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

        $imageRoute = rtrim(config('eloquent_imagery.image_route', '/image'), '/');

        $router->get("{$imageRoute}/{path}", ImageController::class . '@handle')
            ->where('path', '(.*)')
            ->name('eloquent-imagery.image');
    }
}
