<?php

namespace KadirGun\JsonQuery;

use Illuminate\Support\Facades\Route;
use KadirGun\JsonQuery\Http\Controllers\JsonQueryController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JsonQueryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('json-query')
            ->hasConfigFile();
    }

    public function registerRoutes(): void
    {
        if (! config('json-query.route.enabled', true)) {
            return;
        }

        Route::middleware(config('json-query.route.middleware', ['api']))
            ->prefix(config('json-query.route.path', 'json-query'))
            ->name('json-query.')
            ->group(function () {
                Route::post('{model}', JsonQueryController::class)
                    ->name('query')
                    ->where('model', '.*');
            });
    }

    public function bootingPackage()
    {
        $this->registerRoutes();
    }

    public function registeringPackage(): void
    {
        $this->app->bind(JsonQueryData::class, function ($app) {
            return JsonQueryData::fromRequest($app['request']);
        });
    }

    public function packageBooted() {}

    public function provides(): array
    {
        return [
            JsonQueryData::class,
        ];
    }
}
