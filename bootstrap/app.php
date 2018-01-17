<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->configureMonologUsing(function(Monolog\Logger $monolog) {
    $filename = storage_path('logs/laravel-'.php_sapi_name().'.log');
    $handler = new Monolog\Handler\RotatingFileHandler($filename);
    $monolog->pushHandler($handler);
});

//$app['Dingo\Api\Transformer\Factory']->setAdapter(function ($app) {
//    $fractal = new League\Fractal\Manager;
//    $fractal->setSerializer(new App\Http\Controllers\Api\V2\Serializers\NoDataArraySerializer);
//    return new Dingo\Api\Transformer\Adapter\Fractal($fractal);
//});

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

// app('Dingo\Api\Auth\Auth')->extend('jwt', function ($app) {
//    return new Dingo\Api\Auth\Provider\JWT($app['Tymon\JWTAuth\JWTAuth']);
// });

return $app;
